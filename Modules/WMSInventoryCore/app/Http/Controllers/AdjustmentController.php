<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Helpers\FormattingHelper;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\app\Services\WMSInventoryCoreSettingsService;
use Modules\WMSInventoryCore\Models\Adjustment;
use Modules\WMSInventoryCore\Models\AdjustmentProduct;
use Modules\WMSInventoryCore\Models\AdjustmentType;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Warehouse;
use Yajra\DataTables\Facades\DataTables;

class AdjustmentController extends Controller
{
    /**
     * Display a listing of the adjustments.
     *
     * @return Renderable
     */
    public function index()
    {
        $this->authorize('wmsinventory.view-adjustments');
        $warehouses = Warehouse::where('is_active', true)->get();
        $adjustmentTypes = AdjustmentType::all();

        return view('wmsinventorycore::adjustments.index', [
            'warehouses' => $warehouses,
            'adjustmentTypes' => $adjustmentTypes,
        ]);
    }

    /**
     * Process ajax request for adjustments datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        $this->authorize('wmsinventory.view-adjustments');
        $query = Adjustment::with(['warehouse', 'adjustmentType'])
            ->select('adjustments.*');

        // Apply filters
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('adjustment_type_id')) {
            $query->where('adjustment_type_id', $request->adjustment_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('actions', function ($adjustment) {
                $actions = [
                    [
                        'label' => __('View Details'),
                        'icon' => 'bx bx-show',
                        'url' => route('wmsinventorycore.adjustments.show', $adjustment->id),
                    ],
                ];

                if ($adjustment->status === 'pending') {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'url' => route('wmsinventorycore.adjustments.edit', $adjustment->id),
                    ];

                    // Only show approve action if approval is required
                    if (WMSInventoryCoreSettingsService::requireApprovalForAdjustments()) {
                        $actions[] = [
                            'label' => __('Approve'),
                            'icon' => 'bx bx-check',
                            'onclick' => "approveRecord({$adjustment->id})",
                        ];
                    }

                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteRecord({$adjustment->id})",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $adjustment->id,
                    'actions' => $actions,
                ])->render();
            })
            ->editColumn('date', function ($adjustment) {
                return FormattingHelper::formatDate($adjustment->date);
            })
            ->editColumn('warehouse', function ($adjustment) {
                return $adjustment->warehouse ? $adjustment->warehouse->name : '-';
            })
            ->editColumn('adjustment_type', function ($adjustment) {
                return $adjustment->adjustmentType ? $adjustment->adjustmentType->name : '-';
            })
            ->editColumn('total_amount', function ($adjustment) {
                return FormattingHelper::formatCurrency($adjustment->total_amount);
            })
            ->editColumn('status', function ($adjustment) {
                $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'cancelled' => 'danger',
                ];
                $statusColor = $statusColors[$adjustment->status] ?? 'secondary';

                return '<span class="badge bg-label-'.$statusColor.'">'.strtoupper($adjustment->status).'</span>';
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
    }

    /**
     * Show the form for creating a new adjustment.
     *
     * @return Renderable
     */
    public function create()
    {
        $this->authorize('wmsinventory.create-adjustment');
        $warehouses = Warehouse::where('is_active', true)->get();
        $adjustmentTypes = AdjustmentType::all();

        // Generate next adjustment code
        $lastAdjustment = Adjustment::orderBy('id', 'desc')->first();
        $nextCode = 'ADJ-'.str_pad(($lastAdjustment ? intval(substr($lastAdjustment->code, 4)) + 1 : 1), 6, '0', STR_PAD_LEFT);

        return view('wmsinventorycore::adjustments.create', [
            'warehouses' => $warehouses,
            'adjustmentTypes' => $adjustmentTypes,
            'nextCode' => $nextCode,
        ]);
    }

    /**
     * Store a newly created adjustment in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('wmsinventory.create-adjustment');

        // Build validation rules based on settings
        $rules = [
            'date' => 'required|date',
            'reference_no' => 'nullable|string|max:50',
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_type_id' => 'required|exists:adjustment_types,id',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.reason' => 'nullable|string|max:255',
        ];

        // Check if reason is required
        if (WMSInventoryCoreSettingsService::requireReasonForAdjustments()) {
            $rules['reason'] = 'required|string';
        } else {
            $rules['reason'] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        try {
            DB::transaction(function () use ($request, $validated) {
                // Generate adjustment code
                $lastAdjustment = Adjustment::orderBy('id', 'desc')->first();
                $code = 'ADJ-'.str_pad(($lastAdjustment ? intval(substr($lastAdjustment->code, 4)) + 1 : 1), 6, '0', STR_PAD_LEFT);

                // Calculate total amount (will be 0 for now since we don't track unit costs in the basic form)
                $totalAmount = 0;

                // Create adjustment
                $adjustment = Adjustment::create([
                    'date' => $validated['date'],
                    'code' => $code,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'warehouse_id' => $validated['warehouse_id'],
                    'adjustment_type_id' => $validated['adjustment_type_id'],
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);

                // Create adjustment products
                foreach ($request->products as $productData) {
                    AdjustmentProduct::create([
                        'adjustment_id' => $adjustment->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'unit_cost' => 0, // Will be set later when approved
                        'subtotal' => 0,
                        'reason' => $productData['reason'] ?? null,
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.adjustments.index')
                ->with('success', __('Adjustment has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create adjustment: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to create adjustment'))
                ->withInput();
        }
    }

    /**
     * Display the specified adjustment.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->authorize('wmsinventory.view-adjustments');
        $adjustment = Adjustment::with([
            'warehouse',
            'adjustmentType',
            'products.product',
            'approvedBy',
            'createdBy',
            'updatedBy',
        ])->findOrFail($id);

        return view('wmsinventorycore::adjustments.show', [
            'adjustment' => $adjustment,
        ]);
    }

    /**
     * Show the form for editing the specified adjustment.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->authorize('wmsinventory.edit-adjustment');
        $adjustment = Adjustment::with(['warehouse', 'adjustmentType', 'products.product'])
            ->findOrFail($id);

        // Only pending adjustments can be edited
        if ($adjustment->status !== 'pending') {
            return redirect()->route('wmsinventorycore.adjustments.show', $adjustment->id)
                ->with('error', __('Only pending adjustments can be edited'));
        }

        $warehouses = Warehouse::where('is_active', true)->get();
        $adjustmentTypes = AdjustmentType::all();

        return view('wmsinventorycore::adjustments.edit', [
            'adjustment' => $adjustment,
            'warehouses' => $warehouses,
            'adjustmentTypes' => $adjustmentTypes,
        ]);
    }

    /**
     * Update the specified adjustment in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('wmsinventory.edit-adjustment');
        $adjustment = Adjustment::findOrFail($id);

        // Only pending adjustments can be updated
        if ($adjustment->status !== 'pending') {
            return redirect()->route('wmsinventorycore.adjustments.show', $adjustment->id)
                ->with('error', __('Only pending adjustments can be updated'));
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reference_no' => 'nullable|string|max:50',
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_type_id' => 'required|exists:adjustment_types,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $adjustment, $validated) {
                // Calculate total amount (will be 0 for now)
                $totalAmount = 0;

                // Update adjustment
                $adjustment->update([
                    'date' => $validated['date'],
                    'reference_no' => $validated['reference_no'] ?? null,
                    'warehouse_id' => $validated['warehouse_id'],
                    'adjustment_type_id' => $validated['adjustment_type_id'],
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                    'total_amount' => $totalAmount,
                    'updated_by_id' => auth()->id(),
                ]);

                // Remove old adjustment products
                $adjustment->products()->delete();

                // Create new adjustment products
                foreach ($request->products as $productData) {
                    AdjustmentProduct::create([
                        'adjustment_id' => $adjustment->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'unit_cost' => 0, // Will be set later when approved
                        'subtotal' => 0,
                        'reason' => $productData['reason'] ?? null,
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.adjustments.show', $adjustment->id)
                ->with('success', __('Adjustment has been updated successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to update adjustment: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to update adjustment'))
                ->withInput();
        }
    }

    /**
     * Remove the specified adjustment from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('wmsinventory.delete-adjustment');
        try {
            $adjustment = Adjustment::findOrFail($id);

            // Only pending adjustments can be deleted
            if ($adjustment->status !== 'pending') {
                return Error::response(__('Only pending adjustments can be deleted'));
            }

            DB::transaction(function () use ($adjustment) {
                // Delete adjustment products
                $adjustment->products()->delete();

                // Delete the adjustment
                $adjustment->delete();
            });

            return Success::response(__('Adjustment has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete adjustment: '.$e->getMessage());

            return Error::response(__('Failed to delete adjustment'));
        }
    }

    /**
     * Approve the specified adjustment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $this->authorize('wmsinventory.approve-adjustment');
        $adjustment = Adjustment::with(['warehouse', 'adjustmentType', 'products.product'])
            ->findOrFail($id);

        // Only pending adjustments can be approved
        if ($adjustment->status !== 'pending') {
            return redirect()->route('wmsinventorycore.adjustments.show', $adjustment->id)
                ->with('error', __('Only pending adjustments can be approved'));
        }

        try {
            DB::transaction(function () use ($adjustment) {
                // Process each product in the adjustment
                foreach ($adjustment->products as $adjustmentProduct) {
                    $product = $adjustmentProduct->product;
                    $warehouse = $adjustment->warehouse;
                    $quantity = $adjustmentProduct->quantity;

                    // Find or create inventory record
                    $inventory = Inventory::firstOrCreate(
                        ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                        [
                            'stock_level' => 0,
                            'created_by_id' => auth()->id(),
                            'updated_by_id' => auth()->id(),
                        ]
                    );

                    // Update inventory based on adjustment type
                    if ($adjustment->isIncreasing()) {
                        $inventory->increment('stock_level', $quantity);
                        $transactionType = 'adjustment_in';
                    } elseif ($adjustment->isDecreasing()) {
                        // Check if there's enough inventory
                        if ($inventory->stock_level < $quantity) {
                            throw new \Exception("Insufficient stock for product {$product->name} in {$warehouse->name}");
                        }

                        // Check if negative stock is allowed
                        if (! WMSInventoryCoreSettingsService::allowNegativeStock()) {
                            $newStockLevel = $inventory->stock_level - $quantity;
                            if ($newStockLevel < 0) {
                                throw new \Exception("This adjustment would result in negative stock for product {$product->name} in {$warehouse->name} and is not allowed by system settings");
                            }
                        }

                        $inventory->decrement('stock_level', $quantity);
                        $transactionType = 'adjustment_out';
                    }

                    // Record inventory transaction
                    InventoryTransaction::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'transaction_type' => $transactionType,
                        'reference_type' => 'adjustment',
                        'reference_id' => $adjustment->id,
                        'quantity' => $adjustment->isIncreasing() ? $quantity : -$quantity,
                        'stock_before' => $adjustment->isIncreasing() ? ($inventory->stock_level - $quantity) : ($inventory->stock_level + $quantity),
                        'stock_after' => $inventory->stock_level,
                        'unit_id' => $product->unit_id,
                        'notes' => $adjustment->reason,
                        'created_by_id' => auth()->id(),
                    ]);
                }

                // Update adjustment status
                $adjustment->update([
                    'status' => 'approved',
                    'approved_by_id' => auth()->id(),
                    'approved_at' => now(),
                ]);
            });

            return redirect()->route('wmsinventorycore.adjustments.show', $adjustment->id)
                ->with('success', __('Adjustment has been approved successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to approve adjustment: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to approve adjustment: ').$e->getMessage());
        }
    }

    /**
     * Get products for the specified warehouse.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarehouseProducts(Request $request)
    {
        $this->authorize('wmsinventory.view-products');
        $warehouseId = $request->get('warehouse_id');

        if (! $warehouseId) {
            return response()->json(['error' => __('Warehouse ID is required')], 400);
        }

        $products = Product::with([
            'unit',
            'inventory' => function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            },
        ])->get()->map(function ($product) {
            $inventory = $product->inventory->first();

            return [
                'id' => $product->id,
                'text' => $product->name.' ('.$product->sku.')',
                'purchase_price' => $product->purchase_price,
                'current_stock' => $inventory ? $inventory->quantity : 0,
                'unit' => $product->unit ? $product->unit->name : '',
            ];
        });

        return response()->json($products);
    }
}
