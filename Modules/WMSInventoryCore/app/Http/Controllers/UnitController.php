<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\app\Http\Requests\StoreUnitRequest;
use Modules\WMSInventoryCore\app\Http\Requests\UpdateUnitRequest;
use Modules\WMSInventoryCore\Models\Unit;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        $this->authorize('wmsinventory.view-units');

        return view('wmsinventorycore::units.index');
    }

    public function getDataAjax(Request $request)
    {
        $this->authorize('wmsinventory.view-units');
        $units = Unit::withCount('products');

        return DataTables::of($units)
            ->addColumn('actions', function ($unit) {
                $actions = [];

                if (auth()->user()->can('wmsinventory.edit-unit')) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'class' => 'edit-record',
                        'data' => [
                            'id' => $unit->id,
                            'name' => $unit->name,
                            'code' => $unit->code,
                            'description' => $unit->description ?? '',
                            'bs-toggle' => 'offcanvas',
                            'bs-target' => '#offcanvasEditUnit',
                        ],
                    ];
                }

                if (auth()->user()->can('wmsinventory.delete-unit')) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'class' => 'delete-record text-danger',
                        'data-id' => $unit->id,
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $unit->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(StoreUnitRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                Unit::create([
                    'name' => $request->name,
                    'code' => $request->code,
                    'description' => $request->description,
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response(__('Unit has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create unit: '.$e->getMessage());

            return Error::response(__('Failed to create unit'));
        }
    }

    public function update(UpdateUnitRequest $request, $id)
    {
        try {
            $unit = Unit::findOrFail($id);

            DB::transaction(function () use ($request, $unit) {
                $unit->update([
                    'name' => $request->name,
                    'code' => $request->code,
                    'description' => $request->description,
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response(__('Unit has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update unit: '.$e->getMessage());

            return Error::response(__('Failed to update unit'));
        }
    }

    public function destroy($id)
    {
        $this->authorize('wmsinventory.delete-unit');

        try {
            $unit = Unit::findOrFail($id);

            if ($unit->products()->count() > 0) {
                return Error::response(__('Cannot delete unit with existing products'));
            }

            DB::transaction(function () use ($unit) {
                $unit->delete();
            });

            return Success::response(__('Unit has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete unit: '.$e->getMessage());

            return Error::response(__('Failed to delete unit'));
        }
    }
}
