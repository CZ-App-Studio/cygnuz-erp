<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\Models\AdjustmentType;
use Yajra\DataTables\Facades\DataTables;

class AdjustmentTypeController extends Controller
{
    /**
     * Display a listing of the adjustment types.
     *
     * @return Renderable
     */
    public function index()
    {
        $this->authorize('wmsinventory.view-adjustment-types');
        $adjustmentTypes = AdjustmentType::withCount('adjustments')->get();

        return view('wmsinventorycore::adjustment-types.index', [
            'adjustmentTypes' => $adjustmentTypes,
        ]);
    }

    /**
     * Process ajax request for adjustment types datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        $this->authorize('wmsinventory.view-adjustment-types');
        $adjustmentTypes = AdjustmentType::withCount('adjustments')->get();

        return DataTables::of($adjustmentTypes)
            ->addColumn('effect_type', function ($adjustmentType) {
                $effect = strtolower($adjustmentType->effect);
                $badgeClass = in_array($effect, ['add', 'increase']) ? 'bg-label-success' : 'bg-label-danger';
                $icon = in_array($effect, ['add', 'increase']) ? 'bx-plus' : 'bx-minus';

                return '<span class="badge '.$badgeClass.'"><i class="bx '.$icon.' me-1"></i>'.ucfirst($adjustmentType->effect).'</span>';
            })
            ->addColumn('actions', function ($adjustmentType) {
                $actions = [];

                if (auth()->user()->can('wmsinventory.edit-adjustment-type')) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'class' => 'edit-record',
                        'data' => [
                            'bs-toggle' => 'offcanvas',
                            'bs-target' => '#offcanvasEditAdjustmentType',
                            'id' => $adjustmentType->id,
                            'name' => $adjustmentType->name,
                            'description' => $adjustmentType->description ?? '',
                            'effect-type' => $adjustmentType->effect,
                        ],
                    ];
                }

                if (auth()->user()->can('wmsinventory.delete-adjustment-type')) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'class' => 'delete-record text-danger',
                        'data' => ['id' => $adjustmentType->id],
                    ];
                }

                return view('components.datatable-actions', [
                    'actions' => $actions,
                    'id' => $adjustmentType->id,
                ])->render();
            })
            ->rawColumns(['effect_type', 'actions'])
            ->make(true);
    }

    /**
     * Store a newly created adjustment type in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('wmsinventory.create-adjustment-type');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:adjustment_types,name',
            'effect' => 'required|in:increase,decrease',
            'description' => 'nullable|string',
        ]);

        try {
            // Generate unique code from name
            $code = $this->generateUniqueCode($validated['name']);

            AdjustmentType::create([
                'name' => $validated['name'],
                'code' => $code,
                'effect' => $validated['effect'],
                'description' => $validated['description'] ?? null,
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);

            return Success::response(__('Adjustment type has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create adjustment type: '.$e->getMessage());

            return Error::response(__('Failed to create adjustment type'));
        }
    }

    /**
     * Update the specified adjustment type in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('wmsinventory.edit-adjustment-type');
        $adjustmentType = AdjustmentType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:adjustment_types,name,'.$adjustmentType->id,
            'effect' => 'required|in:increase,decrease',
            'description' => 'nullable|string',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'effect' => $validated['effect'],
                'description' => $validated['description'] ?? null,
                'updated_by_id' => auth()->id(),
            ];

            // If name changed, generate new code
            if ($adjustmentType->name !== $validated['name']) {
                $updateData['code'] = $this->generateUniqueCode($validated['name'], $adjustmentType->id);
            }

            $adjustmentType->update($updateData);

            return Success::response(__('Adjustment type has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update adjustment type: '.$e->getMessage());

            return Error::response(__('Failed to update adjustment type'));
        }
    }

    /**
     * Remove the specified adjustment type from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('wmsinventory.delete-adjustment-type');
        try {
            $adjustmentType = AdjustmentType::findOrFail($id);

            // Check if adjustment type has adjustments
            if ($adjustmentType->adjustments()->count() > 0) {
                return Error::response(__('Cannot delete adjustment type with existing adjustments'));
            }

            $adjustmentType->delete();

            return Success::response(__('Adjustment type has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete adjustment type: '.$e->getMessage());

            return Error::response(__('Failed to delete adjustment type'));
        }
    }

    /**
     * Generate a unique code from the name
     *
     * @param  string  $name
     * @param  int|null  $excludeId
     * @return string
     */
    private function generateUniqueCode($name, $excludeId = null)
    {
        // Generate base code from name (first 3 letters of each word, max 6 chars)
        $words = explode(' ', $name);
        $code = '';

        foreach ($words as $word) {
            $code .= strtoupper(substr($word, 0, 3));
            if (strlen($code) >= 6) {
                break;
            }
        }

        // If code is too short, pad with name initials
        if (strlen($code) < 3) {
            $code = strtoupper(substr($name, 0, 3));
        }

        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;

        $query = AdjustmentType::where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $code = $originalCode.$counter;
            $counter++;

            $query = AdjustmentType::where('code', $code);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $code;
    }
}
