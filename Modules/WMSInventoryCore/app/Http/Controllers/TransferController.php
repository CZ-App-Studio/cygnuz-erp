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
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Transfer;
use Modules\WMSInventoryCore\Models\TransferProduct;
use Modules\WMSInventoryCore\Models\Warehouse;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    /**
     * Display a listing of the transfers.
     *
     * @return Renderable
     */
    public function index()
    {
        $this->authorize('wmsinventory.view-transfers');
        $warehouses = Warehouse::where('status', 'active')->get();

        return view('wmsinventorycore::transfers.index', [
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Process ajax request for transfers datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        $this->authorize('wmsinventory.view-transfers');
        $query = Transfer::with(['sourceWarehouse', 'destinationWarehouse'])
            ->select('transfers.*');

        // Apply filters
        if ($request->filled('source_warehouse_id')) {
            $query->where('source_warehouse_id', $request->source_warehouse_id);
        }

        if ($request->filled('destination_warehouse_id')) {
            $query->where('destination_warehouse_id', $request->destination_warehouse_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_range')) {
            $dateRange = explode(' to ', $request->date_range);
            if (count($dateRange) == 2) {
                $query->whereDate('transfer_date', '>=', $dateRange[0])
                    ->whereDate('transfer_date', '<=', $dateRange[1]);
            }
        }

        return DataTables::of($query)
            ->addColumn('actions', function ($transfer) {
                $actions = [
                    [
                        'label' => __('View Details'),
                        'icon' => 'bx bx-show',
                        'url' => route('wmsinventorycore.transfers.show', $transfer->id),
                    ],
                ];

                if ($transfer->status === 'draft') {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'url' => route('wmsinventorycore.transfers.edit', $transfer->id),
                    ];

                    // Only show approve action if approval is required
                    if (WMSInventoryCoreSettingsService::requireApprovalForTransfers()) {
                        $actions[] = [
                            'label' => __('Approve'),
                            'icon' => 'bx bx-check',
                            'onclick' => "approveRecord({$transfer->id})",
                        ];
                    } else {
                        // If approval not required, go directly to Ship
                        $actions[] = [
                            'label' => __('Ship'),
                            'icon' => 'bx bx-package',
                            'onclick' => "shipRecord({$transfer->id})",
                        ];
                    }

                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteRecord({$transfer->id})",
                    ];
                }

                if ($transfer->status === 'approved') {
                    $actions[] = [
                        'label' => __('Ship'),
                        'icon' => 'bx bx-package',
                        'onclick' => "shipRecord({$transfer->id})",
                    ];

                    $actions[] = [
                        'label' => __('Cancel'),
                        'icon' => 'bx bx-x',
                        'onclick' => "cancelRecord({$transfer->id})",
                    ];
                }

                if ($transfer->status === 'in_transit') {
                    $actions[] = [
                        'label' => __('Receive'),
                        'icon' => 'bx bx-check-double',
                        'onclick' => "receiveRecord({$transfer->id})",
                    ];

                    $actions[] = [
                        'label' => __('Cancel'),
                        'icon' => 'bx bx-x',
                        'onclick' => "cancelRecord({$transfer->id})",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $transfer->id,
                    'actions' => $actions,
                ])->render();
            })
            ->editColumn('date', function ($transfer) {
                return FormattingHelper::formatDate($transfer->transfer_date) ?? '-';
            })
            ->editColumn('source_warehouse', function ($transfer) {
                return $transfer->sourceWarehouse ? $transfer->sourceWarehouse->name : '-';
            })
            ->editColumn('destination_warehouse', function ($transfer) {
                return $transfer->destinationWarehouse ? $transfer->destinationWarehouse->name : '-';
            })
            ->editColumn('status', function ($transfer) {
                $statusColors = [
                    'draft' => 'secondary',
                    'approved' => 'info',
                    'in_transit' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ];
                $statusColor = $statusColors[$transfer->status] ?? 'secondary';

                return '<span class="badge bg-label-'.$statusColor.'">'.strtoupper($transfer->status).'</span>';
            })
            ->addColumn('reference', function ($transfer) {
                return $transfer->reference_no ?: $transfer->code;
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
    }

    /**
     * Show the form for creating a new transfer.
     *
     * @return Renderable
     */
    public function create()
    {
        $this->authorize('wmsinventory.create-transfer');
        $warehouses = Warehouse::where('status', 'active')->get();

        // Generate next transfer code
        $lastTransfer = Transfer::orderBy('id', 'desc')->first();
        $nextCode = 'TRF-'.str_pad(($lastTransfer ? intval(substr($lastTransfer->code, 4)) + 1 : 1), 6, '0', STR_PAD_LEFT);

        return view('wmsinventorycore::transfers.create', [
            'warehouses' => $warehouses,
            'nextCode' => $nextCode,
        ]);
    }

    /**
     * Store a newly created transfer in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('wmsinventory.create-transfer');
        $validated = $request->validate([
            'date' => 'required|date',
            'code' => 'required|string|max:50|unique:transfers',
            'reference_no' => 'nullable|string|max:50',
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($request, $validated) {
                // Create transfer
                $transfer = Transfer::create([
                    'transfer_date' => $validated['date'],
                    'code' => $validated['code'],
                    'reference_no' => $validated['reference_no'] ?? null,
                    'source_warehouse_id' => $validated['source_warehouse_id'],
                    'destination_warehouse_id' => $validated['destination_warehouse_id'],
                    'shipping_cost' => $validated['shipping_cost'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'draft',
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);

                // Create transfer products
                foreach ($request->products as $productData) {
                    $product = Product::find($productData['product_id']);
                    TransferProduct::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'unit_id' => $product->unit_id,
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.transfers.index')
                ->with('success', __('Transfer has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create transfer: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to create transfer'))
                ->withInput();
        }
    }

    /**
     * Display the specified transfer.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->authorize('wmsinventory.view-transfers');
        $transfer = Transfer::with([
            'sourceWarehouse',
            'destinationWarehouse',
            'products.product.unit',
            'approvedBy',
            'shippedBy',
            'receivedBy',
            'createdBy',
            'updatedBy',
            'audits.user',
        ])->findOrFail($id);

        return view('wmsinventorycore::transfers.show', [
            'transfer' => $transfer,
        ]);
    }

    /**
     * Show the form for editing the specified transfer.
     *
     * @param  int  $id
     * @return Renderable|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $this->authorize('wmsinventory.edit-transfer');
        $transfer = Transfer::with(['sourceWarehouse', 'destinationWarehouse', 'products.product'])
            ->findOrFail($id);

        // Only draft transfers can be edited
        if ($transfer->status !== 'draft') {
            return redirect()->route('wmsinventorycore.transfers.show', $transfer->id)
                ->with('error', __('Only draft transfers can be edited'));
        }

        $warehouses = Warehouse::where('status', 'active')->get();

        // Get available stock for each product in the transfer
        $availableStock = [];
        if ($transfer->sourceWarehouse) {
            foreach ($transfer->products as $transferProduct) {
                $inventory = Inventory::where('warehouse_id', $transfer->source_warehouse_id)
                    ->where('product_id', $transferProduct->product_id)
                    ->first();
                $availableStock[$transferProduct->product_id] = $inventory ? $inventory->stock_level : 0;
            }
        }

        return view('wmsinventorycore::transfers.edit', [
            'transfer' => $transfer,
            'warehouses' => $warehouses,
            'availableStock' => $availableStock,
        ]);
    }

    /**
     * Update the specified transfer in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('wmsinventory.edit-transfer');
        $transfer = Transfer::findOrFail($id);

        // Only draft transfers can be updated
        if ($transfer->status !== 'draft') {
            return redirect()->route('wmsinventorycore.transfers.show', $transfer->id)
                ->with('error', __('Only draft transfers can be updated'));
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reference_no' => 'nullable|string|max:50',
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($request, $transfer, $validated) {
                // Update transfer
                $transfer->update([
                    'transfer_date' => $validated['date'],
                    'reference_no' => $validated['reference_no'] ?? null,
                    'source_warehouse_id' => $validated['source_warehouse_id'],
                    'destination_warehouse_id' => $validated['destination_warehouse_id'],
                    'shipping_cost' => $validated['shipping_cost'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by_id' => auth()->id(),
                ]);

                // Remove old transfer products
                $transfer->products()->delete();

                // Create new transfer products
                foreach ($request->products as $productData) {
                    $product = Product::find($productData['product_id']);
                    TransferProduct::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'unit_id' => $product->unit_id,
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.transfers.show', $transfer->id)
                ->with('success', __('Transfer has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update transfer: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to update transfer'))
                ->withInput();
        }
    }

    /**
     * Remove the specified transfer from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('wmsinventory.delete-transfer');
        try {
            $transfer = Transfer::findOrFail($id);

            // Only draft transfers can be deleted
            if ($transfer->status !== 'draft') {
                return Error::response(__('Only draft transfers can be deleted'));
            }

            DB::transaction(function () use ($transfer) {
                // Delete transfer products
                $transfer->products()->delete();

                // Delete the transfer
                $transfer->delete();
            });

            return Success::response(__('Transfer has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete transfer: '.$e->getMessage());

            return Error::response(__('Failed to delete transfer'));
        }
    }

    /**
     * Approve the specified transfer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $this->authorize('wmsinventory.approve-transfer');
        $transfer = Transfer::with(['sourceWarehouse', 'destinationWarehouse', 'products.product'])
            ->findOrFail($id);

        // Only draft transfers can be approved
        if ($transfer->status !== 'draft') {
            return redirect()->route('wmsinventorycore.transfers.show', $transfer->id)
                ->with('error', __('Only draft transfers can be approved'));
        }

        try {
            DB::transaction(function () use ($transfer) {
                // Check if we have enough stock for all products
                foreach ($transfer->products as $transferProduct) {
                    $product = $transferProduct->product;
                    $quantity = $transferProduct->quantity;

                    // Check source warehouse inventory
                    $sourceInventory = Inventory::where([
                        'product_id' => $product->id,
                        'warehouse_id' => $transfer->source_warehouse_id,
                    ])->first();

                    if (! $sourceInventory || $sourceInventory->stock_level < $quantity) {
                        throw new \Exception("Insufficient stock for product {$product->name} in source warehouse");
                    }
                }

                // Update transfer status to approved
                $transfer->update([
                    'status' => 'approved',
                    'approved_by_id' => auth()->id(),
                    'approved_at' => now(),
                ]);
            });

            return redirect()->route('wmsinventorycore.transfers.show', $transfer->id)
                ->with('success', __('Transfer has been approved successfully. You can now ship it.'));
        } catch (\Exception $e) {
            Log::error('Failed to approve transfer: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to approve transfer: ').$e->getMessage());
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
        $search = $request->get('q', '');

        if (! $warehouseId) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
        }

        $query = Product::with([
            'unit',
            'inventories' => function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            },
        ]);

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        $products = $query->get()->map(function ($product) {
            $inventory = $product->inventories->first();
            $stockLevel = $inventory ? $inventory->stock_level : 0;

            return [
                'id' => $product->id,
                'text' => $product->name.' ('.$product->sku.')',
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $stockLevel,
                'unit' => $product->unit ? $product->unit->name : '',
            ];
        })->filter(function ($product) {
            // Only include products with stock
            return $product['current_stock'] > 0;
        })->values();

        return response()->json([
            'results' => $products,
            'pagination' => ['more' => false],
        ]);
    }

    /**
     * Ship a transfer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function ship(Request $request, $id)
    {
        $this->authorize('wmsinventory.ship-transfer');
        try {
            DB::beginTransaction();

            $transfer = Transfer::findOrFail($id);

            // Validate the transfer can be shipped
            if ($transfer->status !== 'approved') {
                return response()->json([
                    'message' => __('Only approved transfers can be shipped.'),
                ], 422);
            }

            // Validate request
            $request->validate([
                'actual_ship_date' => 'nullable|date',
                'shipping_notes' => 'nullable|string|max:1000',
            ]);

            // Check if we have enough stock
            foreach ($transfer->products as $transferProduct) {
                $inventory = Inventory::where('warehouse_id', $transfer->source_warehouse_id)
                    ->where('product_id', $transferProduct->product_id)
                    ->first();

                if (! $inventory || $inventory->stock_level < $transferProduct->quantity) {
                    return response()->json([
                        'message' => __('Insufficient stock for product: :product', [
                            'product' => $transferProduct->product->name,
                        ]),
                    ], 422);
                }
            }

            // Deduct stock from source warehouse and create transactions
            foreach ($transfer->products as $transferProduct) {
                $inventory = Inventory::where('warehouse_id', $transfer->source_warehouse_id)
                    ->where('product_id', $transferProduct->product_id)
                    ->first();

                // Check if negative stock is allowed
                if (! WMSInventoryCoreSettingsService::allowNegativeStock()) {
                    $newStockLevel = $inventory->stock_level - $transferProduct->quantity;
                    if ($newStockLevel < 0) {
                        return response()->json([
                            'message' => __('Transfer would result in negative stock for product: :product and is not allowed by system settings', [
                                'product' => $transferProduct->product->name,
                            ]),
                        ], 422);
                    }
                }

                $inventory->stock_level -= $transferProduct->quantity;
                $inventory->save();

                // Create inventory transaction
                InventoryTransaction::create([
                    'warehouse_id' => $transfer->source_warehouse_id,
                    'product_id' => $transferProduct->product_id,
                    'transaction_type' => 'transfer_out',
                    'reference_type' => 'transfer',
                    'reference_id' => $transfer->id,
                    'quantity' => -$transferProduct->quantity,
                    'stock_before' => $inventory->stock_level + $transferProduct->quantity,
                    'stock_after' => $inventory->stock_level,
                    'unit_id' => $transferProduct->product->unit_id,
                    'notes' => 'Stock deducted for transfer #'.$transfer->id,
                    'created_by_id' => auth()->id(),
                ]);
            }

            // Update transfer status
            $transfer->update([
                'status' => 'in_transit',
                'shipped_at' => $request->actual_ship_date ? $request->actual_ship_date : now(),
                'shipped_by_id' => auth()->id(),
                'shipping_notes' => $request->shipping_notes,
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Transfer shipped successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer ship error: '.$e->getMessage());

            return response()->json([
                'message' => __('An error occurred while shipping the transfer.'),
            ], 500);
        }
    }

    /**
     * Receive a transfer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function receive(Request $request, $id)
    {
        $this->authorize('wmsinventory.receive-transfer');
        try {
            DB::beginTransaction();

            $transfer = Transfer::findOrFail($id);

            // Validate the transfer can be received
            if ($transfer->status !== 'in_transit') {
                return response()->json([
                    'message' => __('Only shipped transfers can be received.'),
                ], 422);
            }

            // Validate request
            $request->validate([
                'actual_arrival_date' => 'nullable|date',
                'receiving_notes' => 'nullable|string|max:1000',
            ]);

            // Add stock to destination warehouse and create transactions
            foreach ($transfer->products as $transferProduct) {
                $inventory = Inventory::firstOrCreate([
                    'warehouse_id' => $transfer->destination_warehouse_id,
                    'product_id' => $transferProduct->product_id,
                ], [
                    'stock_level' => 0,
                    'unit_cost' => 0,
                    'total_cost' => 0,
                ]);

                $inventory->stock_level += $transferProduct->quantity;
                $inventory->save();

                // Create inventory transaction
                InventoryTransaction::create([
                    'warehouse_id' => $transfer->destination_warehouse_id,
                    'product_id' => $transferProduct->product_id,
                    'transaction_type' => 'transfer_in',
                    'reference_type' => 'transfer',
                    'reference_id' => $transfer->id,
                    'quantity' => $transferProduct->quantity,
                    'stock_before' => $inventory->stock_level - $transferProduct->quantity,
                    'stock_after' => $inventory->stock_level,
                    'unit_id' => $transferProduct->product->unit_id,
                    'notes' => 'Stock received from transfer #'.$transfer->id,
                    'created_by_id' => auth()->id(),
                ]);
            }

            // Update transfer status
            $transfer->update([
                'status' => 'completed',
                'received_at' => $request->actual_arrival_date ? $request->actual_arrival_date : now(),
                'received_by_id' => auth()->id(),
                'receiving_notes' => $request->receiving_notes,
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Transfer received successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer receive error: '.$e->getMessage());

            return response()->json([
                'message' => __('An error occurred while receiving the transfer.'),
            ], 500);
        }
    }

    /**
     * Cancel a transfer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        $this->authorize('wmsinventory.cancel-transfer');
        try {
            DB::beginTransaction();

            $transfer = Transfer::findOrFail($id);

            // Validate the transfer can be cancelled
            if (! in_array($transfer->status, ['approved', 'in_transit'])) {
                return response()->json([
                    'message' => __('Only approved or in-transit transfers can be cancelled.'),
                ], 422);
            }

            // Validate request
            $request->validate([
                'cancellation_reason' => 'required|string|max:1000',
            ]);

            // If transfer was shipped, we need to restore stock to source warehouse
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->products as $transferProduct) {
                    $inventory = Inventory::where('warehouse_id', $transfer->source_warehouse_id)
                        ->where('product_id', $transferProduct->product_id)
                        ->first();

                    if ($inventory) {
                        $inventory->stock_level += $transferProduct->quantity;
                        $inventory->save();

                        // Create inventory transaction for stock restoration
                        InventoryTransaction::create([
                            'warehouse_id' => $transfer->source_warehouse_id,
                            'product_id' => $transferProduct->product_id,
                            'transaction_type' => 'transfer_cancel',
                            'reference_type' => 'transfer',
                            'reference_id' => $transfer->id,
                            'quantity' => $transferProduct->quantity,
                            'stock_before' => $inventory->stock_level - $transferProduct->quantity,
                            'stock_after' => $inventory->stock_level,
                            'unit_id' => $transferProduct->product->unit_id,
                            'notes' => 'Stock restored from cancelled transfer #'.$transfer->id,
                            'created_by_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by_id' => auth()->id(),
                'cancelled_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Transfer cancelled successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer cancel error: '.$e->getMessage());

            return response()->json([
                'message' => __('An error occurred while cancelling the transfer.'),
            ], 500);
        }
    }
}
