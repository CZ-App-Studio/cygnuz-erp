<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\BaseApiController;
use Modules\WMSInventoryCore\Models\Transfer;
use Modules\WMSInventoryCore\Models\TransferProduct;
use Modules\WMSInventoryCore\Models\Warehouse;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;

class TransferController extends BaseApiController
{
    /**
     * Display a listing of transfers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Transfer::with(['fromWarehouse', 'toWarehouse', 'createdBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by warehouse
            if ($request->has('warehouse_id')) {
                $warehouseId = $request->warehouse_id;
                $query->where(function ($q) use ($warehouseId) {
                    $q->where('from_warehouse_id', $warehouseId)
                      ->orWhere('to_warehouse_id', $warehouseId);
                });
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('transfer_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('transfer_date', '<=', $request->to_date);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'LIKE', "%{$search}%")
                      ->orWhere('notes', 'LIKE', "%{$search}%");
                });
            }

            // Include products
            if ($request->boolean('with_products')) {
                $query->with('transferProducts.product');
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $transfers = $query->paginate($request->input('per_page', 20));

            return $this->paginatedResponse($transfers, 'Transfers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transfers', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created transfer
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'transfer_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:transfer_date',
            'reason' => 'required|in:stock_transfer,rebalancing,consolidation,emergency,other',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            // Validate stock availability
            $stockValidation = $this->validateStockAvailability(
                $request->from_warehouse_id,
                $request->products
            );

            if (!$stockValidation['valid']) {
                return $this->errorResponse('Insufficient stock', $stockValidation['errors'], 400);
            }

            // Create transfer
            $transferData = $request->except('products');
            $transferData['reference'] = $this->generateTransferReference();
            $transferData['status'] = 'pending';
            $transferData['created_by_id'] = auth()->id();

            $transfer = Transfer::create($transferData);

            // Create transfer products
            foreach ($request->products as $productData) {
                TransferProduct::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'notes' => $productData['notes'] ?? null,
                ]);
            }

            // Load relationships
            $transfer->load(['fromWarehouse', 'toWarehouse', 'transferProducts.product']);

            DB::commit();

            return $this->successResponse($transfer, 'Transfer created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified transfer
     */
    public function show($id): JsonResponse
    {
        try {
            $transfer = Transfer::with([
                'fromWarehouse',
                'toWarehouse',
                'transferProducts.product',
                'createdBy',
                'updatedBy',
                'approvedBy',
                'receivedBy'
            ])->findOrFail($id);

            return $this->successResponse($transfer, 'Transfer retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Transfer not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified transfer
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transfer_date' => 'sometimes|required|date',
            'expected_date' => 'nullable|date|after_or_equal:transfer_date',
            'reason' => 'sometimes|required|in:stock_transfer,rebalancing,consolidation,emergency,other',
            'notes' => 'nullable|string',
            'products' => 'sometimes|required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $transfer = Transfer::findOrFail($id);

            // Can only update pending transfers
            if ($transfer->status !== 'pending') {
                return $this->errorResponse('Can only update pending transfers', null, 400);
            }

            DB::beginTransaction();

            // Update transfer details
            $transferData = $request->except('products');
            $transferData['updated_by_id'] = auth()->id();
            $transfer->update($transferData);

            // Update products if provided
            if ($request->has('products')) {
                // Validate stock availability
                $stockValidation = $this->validateStockAvailability(
                    $transfer->from_warehouse_id,
                    $request->products
                );

                if (!$stockValidation['valid']) {
                    DB::rollBack();
                    return $this->errorResponse('Insufficient stock', $stockValidation['errors'], 400);
                }

                // Delete existing products
                $transfer->transferProducts()->delete();

                // Add updated products
                foreach ($request->products as $productData) {
                    TransferProduct::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'notes' => $productData['notes'] ?? null,
                    ]);
                }
            }

            // Load relationships
            $transfer->load(['fromWarehouse', 'toWarehouse', 'transferProducts.product']);

            DB::commit();

            return $this->successResponse($transfer, 'Transfer updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->notFoundResponse('Transfer not found');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified transfer
     */
    public function destroy($id): JsonResponse
    {
        try {
            $transfer = Transfer::findOrFail($id);

            // Can only delete pending transfers
            if ($transfer->status !== 'pending') {
                return $this->errorResponse('Can only delete pending transfers', null, 400);
            }

            DB::beginTransaction();

            // Delete transfer products
            $transfer->transferProducts()->delete();

            // Delete transfer
            $transfer->delete();

            DB::commit();

            return $this->successResponse(null, 'Transfer deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->notFoundResponse('Transfer not found');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Approve a transfer
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $transfer = Transfer::findOrFail($id);

            // Check if transfer is pending
            if ($transfer->status !== 'pending') {
                return $this->errorResponse('Transfer is not pending approval', null, 400);
            }

            DB::beginTransaction();

            // Update transfer status
            $transfer->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes,
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return $this->successResponse($transfer, 'Transfer approved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->notFoundResponse('Transfer not found');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to approve transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Ship a transfer
     */
    public function ship(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipped_date' => 'required|date',
            'shipping_notes' => 'nullable|string',
            'tracking_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $transfer = Transfer::with('transferProducts')->findOrFail($id);

            // Check if transfer is approved
            if ($transfer->status !== 'approved') {
                return $this->errorResponse('Transfer must be approved before shipping', null, 400);
            }

            DB::beginTransaction();

            // Validate stock availability
            $stockValidation = $this->validateStockAvailability(
                $transfer->from_warehouse_id,
                $transfer->transferProducts->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ];
                })->toArray()
            );

            if (!$stockValidation['valid']) {
                DB::rollBack();
                return $this->errorResponse('Insufficient stock', $stockValidation['errors'], 400);
            }

            // Deduct inventory from source warehouse
            foreach ($transfer->transferProducts as $transferProduct) {
                $inventory = Inventory::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $transferProduct->product_id)
                    ->firstOrFail();

                $inventory->decrement('quantity', $transferProduct->quantity);

                // Create inventory transaction
                InventoryTransaction::create([
                    'product_id' => $transferProduct->product_id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'type' => 'transfer_out',
                    'quantity' => -$transferProduct->quantity,
                    'reference_type' => Transfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Transfer to {$transfer->toWarehouse->name}",
                    'user_id' => auth()->id(),
                ]);
            }

            // Update transfer status
            $transfer->update([
                'status' => 'in_transit',
                'shipped_date' => $request->shipped_date,
                'shipping_notes' => $request->shipping_notes,
                'tracking_number' => $request->tracking_number,
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return $this->successResponse($transfer, 'Transfer shipped successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to ship transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Receive a transfer
     */
    public function receive(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'received_date' => 'required|date',
            'receiving_notes' => 'nullable|string',
            'received_quantities' => 'required|array',
            'received_quantities.*.product_id' => 'required|exists:products,id',
            'received_quantities.*.quantity' => 'required|numeric|min:0',
            'received_quantities.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $transfer = Transfer::with('transferProducts')->findOrFail($id);

            // Check if transfer is in transit
            if ($transfer->status !== 'in_transit') {
                return $this->errorResponse('Transfer must be in transit to receive', null, 400);
            }

            DB::beginTransaction();

            // Process received quantities
            $totalDiscrepancy = 0;
            foreach ($request->received_quantities as $receivedItem) {
                $transferProduct = $transfer->transferProducts
                    ->where('product_id', $receivedItem['product_id'])
                    ->first();

                if (!$transferProduct) {
                    continue;
                }

                $receivedQty = $receivedItem['quantity'];
                $expectedQty = $transferProduct->quantity;
                $discrepancy = $receivedQty - $expectedQty;

                // Update transfer product with received info
                $transferProduct->update([
                    'received_quantity' => $receivedQty,
                    'discrepancy' => $discrepancy,
                    'receiving_notes' => $receivedItem['notes'] ?? null,
                ]);

                // Add inventory to destination warehouse
                $inventory = Inventory::firstOrCreate(
                    [
                        'warehouse_id' => $transfer->to_warehouse_id,
                        'product_id' => $receivedItem['product_id'],
                    ],
                    [
                        'quantity' => 0,
                        'reorder_point' => 0,
                        'maximum_stock' => 0,
                    ]
                );

                $inventory->increment('quantity', $receivedQty);

                // Create inventory transaction
                InventoryTransaction::create([
                    'product_id' => $receivedItem['product_id'],
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'type' => 'transfer_in',
                    'quantity' => $receivedQty,
                    'reference_type' => Transfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Transfer from {$transfer->fromWarehouse->name}",
                    'user_id' => auth()->id(),
                ]);

                $totalDiscrepancy += abs($discrepancy);
            }

            // Update transfer status
            $status = $totalDiscrepancy > 0 ? 'completed_with_discrepancy' : 'completed';
            
            $transfer->update([
                'status' => $status,
                'received_date' => $request->received_date,
                'received_by' => auth()->id(),
                'received_at' => now(),
                'receiving_notes' => $request->receiving_notes,
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return $this->successResponse($transfer, 'Transfer received successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to receive transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a transfer
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $transfer = Transfer::findOrFail($id);

            // Check if transfer can be cancelled
            if (in_array($transfer->status, ['completed', 'completed_with_discrepancy', 'cancelled'])) {
                return $this->errorResponse('Cannot cancel this transfer', null, 400);
            }

            DB::beginTransaction();

            // If in transit, reverse inventory changes
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->transferProducts as $transferProduct) {
                    // Restore inventory to source warehouse
                    $inventory = Inventory::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('product_id', $transferProduct->product_id)
                        ->first();

                    if ($inventory) {
                        $inventory->increment('quantity', $transferProduct->quantity);

                        // Create reversal transaction
                        InventoryTransaction::create([
                            'product_id' => $transferProduct->product_id,
                            'warehouse_id' => $transfer->from_warehouse_id,
                            'type' => 'transfer_cancel',
                            'quantity' => $transferProduct->quantity,
                            'reference_type' => Transfer::class,
                            'reference_id' => $transfer->id,
                            'notes' => 'Transfer cancelled - inventory restored',
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return $this->successResponse($transfer, 'Transfer cancelled successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to cancel transfer', $e->getMessage(), 500);
        }
    }

    /**
     * Validate stock availability for transfer
     */
    private function validateStockAvailability($warehouseId, $products): array
    {
        $errors = [];
        $valid = true;

        foreach ($products as $productData) {
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_id', $productData['product_id'])
                ->first();

            if (!$inventory || $inventory->quantity < $productData['quantity']) {
                $product = Product::find($productData['product_id']);
                $available = $inventory ? $inventory->quantity : 0;
                $errors[] = [
                    'product' => $product->name,
                    'requested' => $productData['quantity'],
                    'available' => $available,
                ];
                $valid = false;
            }
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
        ];
    }

    /**
     * Generate transfer reference
     */
    private function generateTransferReference(): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $lastTransfer = Transfer::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTransfer 
            ? intval(substr($lastTransfer->reference, -4)) + 1 
            : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}