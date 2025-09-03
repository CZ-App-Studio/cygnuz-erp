<?php

namespace Modules\AccountingCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AccountingCore\app\Models\TaxRate;
use Yajra\DataTables\Facades\DataTables;

class TaxRateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:accountingcore.tax-rates.index')->only(['index', 'getDataAjax']);
        $this->middleware('permission:accountingcore.tax-rates.show')->only(['getTaxRateAjax']);
        $this->middleware('permission:accountingcore.tax-rates.store')->only(['store']);
        $this->middleware('permission:accountingcore.tax-rates.update')->only(['update']);
        $this->middleware('permission:accountingcore.tax-rates.destroy')->only(['destroy']);
        $this->middleware('permission:accountingcore.tax-rates.active')->only(['getActiveTaxRates']);
    }

    public function index()
    {
        return view('accountingcore::tax-rates.index');
    }

    public function getDataAjax(Request $request)
    {
        $query = TaxRate::query()->orderBy('rate', 'asc');

        return DataTables::of($query)
            ->addColumn('rate_formatted', fn ($tax) => $tax->rate.'%')
            ->addColumn('is_default_display', function ($tax) {
                return $tax->is_default ? '<span class="badge bg-label-success">'.__('Yes').'</span>' : '<span class="badge bg-label-secondary">'.__('No').'</span>';
            })
            ->addColumn('type_display', function ($tax) {
                return match ($tax->type) {
                    'percentage' => '<span class="badge bg-label-primary">'.__('Percentage').'</span>',
                    'fixed' => '<span class="badge bg-label-info">'.__('Fixed Amount').'</span>',
                    default => '<span class="badge bg-label-secondary">'.__('Unknown').'</span>',
                };
            })
            ->addColumn('status_display', function ($tax) {
                return $tax->is_active ? '<span class="badge bg-label-success">'.__('Active').'</span>' : '<span class="badge bg-label-warning">'.__('Inactive').'</span>';
            })
            ->addColumn('actions', function ($tax) {
                $actions = [];

                if (auth()->user()->can('accountingcore.tax-rates.update')) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editTaxRate({$tax->id})",
                    ];
                }

                if (auth()->user()->can('accountingcore.tax-rates.destroy')) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteTaxRate({$tax->id})",
                        'class' => 'text-danger',
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $tax->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['is_default_display', 'type_display', 'status_display', 'actions'])
            ->make(true);
    }

    public function getTaxRateAjax(TaxRate $taxRate)
    {
        return response()->json($taxRate);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:tax_rates,name',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:percentage,fixed',
            'description' => 'nullable|string|max:500',
            'tax_authority' => 'nullable|string|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ]);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            $data['is_default'] = $request->boolean('is_default');
            $data['is_active'] = $request->boolean('is_active', true);

            // If this is set as default, remove default from others
            if ($data['is_default']) {
                TaxRate::where('is_default', true)->update(['is_default' => false]);
            }

            TaxRate::create($data);

            DB::commit();

            return Success::response([
                'message' => __('Tax Rate created successfully.'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('TaxRate Store Error: '.$e->getMessage());

            return Error::response(__('Failed to create tax rate.'));
        }
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:tax_rates,name,'.$taxRate->id,
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:percentage,fixed',
            'description' => 'nullable|string|max:500',
            'tax_authority' => 'nullable|string|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ]);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            $data['is_default'] = $request->boolean('is_default');
            $data['is_active'] = $request->boolean('is_active', true);

            // If this is set as default, remove default from others
            if ($data['is_default']) {
                TaxRate::where('id', '!=', $taxRate->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $taxRate->update($data);

            DB::commit();

            return Success::response([
                'message' => __('Tax Rate updated successfully.'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('TaxRate Update Error: '.$e->getMessage());

            return Error::response(__('Failed to update tax rate.'));
        }
    }

    public function destroy(TaxRate $taxRate)
    {
        try {
            // Check if this tax rate is in use
            if ($taxRate->isInUse()) {
                return Error::response(__('Cannot delete: Tax rate is currently in use.'));
            }

            // Prevent deletion of default tax rate if it's the only one
            if ($taxRate->is_default && TaxRate::count() > 1) {
                return Error::response(__('Cannot delete the default tax rate. Set another as default first.'));
            }

            $taxRate->delete();

            return Success::response([
                'message' => __('Tax Rate deleted successfully.'),
            ]);
        } catch (Exception $e) {
            Log::error('TaxRate Delete Error: '.$e->getMessage());

            return Error::response(__('Failed to delete tax rate.'));
        }
    }

    /**
     * Get active tax rates for AJAX dropdowns
     */
    public function getActiveTaxRates(Request $request)
    {
        $taxRates = TaxRate::active()
            ->select('id', 'name', 'rate', 'type')
            ->orderBy('name')
            ->get()
            ->map(function ($taxRate) {
                return [
                    'id' => $taxRate->id,
                    'text' => $taxRate->name.' ('.$taxRate->rate.'%)',
                    'rate' => $taxRate->rate,
                    'type' => $taxRate->type,
                ];
            });

        return response()->json($taxRates);
    }
}
