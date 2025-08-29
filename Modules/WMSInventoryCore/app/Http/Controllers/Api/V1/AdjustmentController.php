<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\BaseApiController;
use Modules\WMSInventoryCore\Models\Adjustment;
use Modules\WMSInventoryCore\Models\AdjustmentItem;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\app\Http\Requests\StoreAdjustmentRequest;
use Modules\WMSInventoryCore\app\Http\Requests\UpdateAdjustmentRequest;

class AdjustmentController extends BaseApiController
{
    /**
     * Display a listing of adjustments.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Adjustment::with(['warehouse', 'adjustedBy', 'approvedBy']);

            // Filters
            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            if ($request->has('adjustment_type')) {
                $query->where('adjustment_type', $request->adjustment_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('reason')) {
                $query->where('reason', $request->reason);
            }

            if ($request->has('from_date')) {
                $query->whereDate('adjustment_date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('adjustment_date', '<=', $request->to_date);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('adjustment_number', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'adjustment_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $adjustments = $query->paginate($perPage);

            return $this->paginatedResponse($adjustments, 'Adjustments retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error fetching adjustments', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch adjustments', 500);
        }
    }

    /**
     * Store a newly created adjustment.
     */
    public function store(StoreAdjustmentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate adjustment number
            $adjustmentNumber = $this->generateAdjustmentNumber();

            // Create adjustment
            $adjustment = Adjustment::create([
                'adjustment_number' => $adjustmentNumber,
                'warehouse_id' => $request->warehouse_id,
                'adjustment_type' => $request->adjustment_type,
                'adjustment_date' => $request->adjustment_date ?? now(),
                'reason' => $request->reason,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'status' => 'draft',
                'adjusted_by_id' => auth()->id(),
                'total_value' => 0
            ]);

            $totalValue = 0;

            // Create adjustment items
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Product not found: {$item['product_id']}");
                }

                $inventory = Inventory::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $request->warehouse_id)
                    ->first();

                $currentQuantity = $inventory ? $inventory->quantity : 0;
                $quantityChange = $request->adjustment_type === 'increase' ? $item['quantity'] : -$item['quantity'];
                $newQuantity = $currentQuantity + $quantityChange;

                // Check if adjustment would result in negative inventory
                if ($newQuantity < 0 && $request->adjustment_type === 'decrease') {
                    throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$currentQuantity}, Requested: {$item['quantity']}");
                }

                $unitCost = $item['unit_cost'] ?? $product->unit_cost ?? 0;
                $lineTotal = abs($item['quantity']) * $unitCost;

                AdjustmentItem::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id' => $item['product_id'],
                    'quantity_before' => $currentQuantity,
                    'quantity_adjusted' => $item['quantity'],
                    'quantity_after' => $newQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'reason' => $item['reason'] ?? null,
                    'notes' => $item['notes'] ?? null
                ]);

                $totalValue += $lineTotal;
            }

            // Update total value
            $adjustment->update(['total_value' => $totalValue]);

            // Load relationships
            $adjustment->load(['warehouse', 'adjustedBy', 'items.product']);

            DB::commit();

            return $this->successResponse($adjustment, 'Adjustment created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating adjustment', ['error' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified adjustment.
     */
    public function show($id): JsonResponse
    {
        try {
            $adjustment = Adjustment::with([
                'warehouse',
                'adjustedBy',
                'approvedBy',
                'items.product',
                'items.product.unit',
                'items.product.category'
            ])->find($id);

            if (!$adjustment) {
                return $this->errorResponse('Adjustment not found', 404);
            }

            return $this->successResponse($adjustment);
        } catch (\Exception $e) {
            Log::error('Error fetching adjustment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch adjustment', 500);
        }
    }

    /**
     * Update the specified adjustment.
     */
    public function update(UpdateAdjustmentRequest $request, $id): JsonResponse
    {
        try {
            $adjustment = Adjustment::find($id);
            if (!$adjustment) {
                return $this->errorResponse('Adjustment not found', 404);
            }

            if ($adjustment->status !== 'draft') {
                return $this->errorResponse('Only draft adjustments can be edited', 400);
            }

            DB::beginTransaction();

            // Update adjustment
            $adjustment->update($request->only([
                'warehouse_id',
                'adjustment_type',
                'adjustment_date',
                'reason',
                'reference_number',
                'notes'
            ]));

            // Update items if provided
            if ($request->has('items')) {
                // Delete existing items
                $adjustment->items()->delete();

                $totalValue = 0;

                // Create new items
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product not found: {$item['product_id']}");
                    }

                    $inventory = Inventory::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $adjustment->warehouse_id)
                        ->first();

                    $currentQuantity = $inventory ? $inventory->quantity : 0;
                    $quantityChange = $adjustment->adjustment_type === 'increase' ? $item['quantity'] : -$item['quantity'];
                    $newQuantity = $currentQuantity + $quantityChange;

                    if ($newQuantity < 0 && $adjustment->adjustment_type === 'decrease') {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }

                    $unitCost = $item['unit_cost'] ?? $product->unit_cost ?? 0;
                    $lineTotal = abs($item['quantity']) * $unitCost;

                    AdjustmentItem::create([
                        'adjustment_id' => $adjustment->id,
                        'product_id' => $item['product_id'],
                        'quantity_before' => $currentQuantity,
                        'quantity_adjusted' => $item['quantity'],
                        'quantity_after' => $newQuantity,
                        'unit_cost' => $unitCost,
                        'total_cost' => $lineTotal,
                        'reason' => $item['reason'] ?? null,
                        'notes' => $item['notes'] ?? null
                    ]);

                    $totalValue += $lineTotal;
                }

                $adjustment->update(['total_value' => $totalValue]);
            }

            $adjustment->load(['warehouse', 'adjustedBy', 'items.product']);

            DB::commit();

            return $this->successResponse($adjustment, 'Adjustment updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating adjustment', ['error' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove the specified adjustment.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $adjustment = Adjustment::find($id);
            if (!$adjustment) {
                return $this->errorResponse('Adjustment not found', 404);
            }

            if ($adjustment->status !== 'draft') {
                return $this->errorResponse('Only draft adjustments can be deleted', 400);
            }

            DB::beginTransaction();

            // Delete items first
            $adjustment->items()->delete();

            // Delete adjustment
            $adjustment->delete();

            DB::commit();

            return $this->successResponse(null, 'Adjustment deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting adjustment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to delete adjustment', 500);
        }
    }

    /**
     * Submit adjustment for approval.
     */
    public function submit($id): JsonResponse
    {
        try {
            $adjustment = Adjustment::find($id);
            if (!$adjustment) {
                return $this->errorResponse('Adjustment not found', 404);
            }

            if ($adjustment->status !== 'draft') {
                return $this->errorResponse('Only draft adjustments can be submitted', 400);
            }

            if ($adjustment->items->isEmpty()) {
                return $this->errorResponse('Cannot submit adjustment without items', 400);
            }

            $adjustment->update([
                'status' => 'pending',
                'submitted_at' => now()
            ]);

            return $this->successResponse($adjustment, 'Adjustment submitted for approval');
        } catch (\Exception $e) {
            Log::error('Error submitting adjustment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to submit adjustment', 500);
        }
    }

    /**
     * Approve adjustment.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        try {
            $adjustment = Adjustment::with('items')->find($id);
            if (!$adjustment) {
                return $this->errorResponse('Adjustment not found', 404);
            }

            if ($adjustment->status !== 'pending') {
                return $this->errorResponse('Only pending adjustments can be approved', 400);
            }

            DB::beginTransaction();

            // Update inventory for each item
            foreach ($adjustment->items as $item) {
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $adjustment->warehouse_id
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        'reorder_point' => 0,
                        'reorder_quantity' => 0
                    ]
                );

                $quantityChange = $adjustment->adjustment_type === 'increase' 
                    ? $item->quantity_adjusted 
                    : -$item->quantity_adjusted;

                $newQuantity = $inventory->quantity + $quantityChange;

                if ($newQuantity < 0) {
                    throw new \Exception("Adjustment would result in negative inventory for product ID: {$item->product_id}");
                }

                // Update inventory
                $inventory->update([
                    'quantity' => $newQuantity,
                    'last_updated' => now()
                ]);

                // Create inventory transaction
                InventoryTransaction::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'transaction_type' => 'adjustment',
                    'transaction_date' => now(),
                    'quantity' => abs($quantityChange),
                    'movement_type' => $adjustment->adjustment_type === 'increase' ? 'in' : 'out',
                    'reference_type' => 'adjustment',
                    'reference_id' => $adjustment->id,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'balance_after' => $newQuantity,
                    'notes' => "Adjustment #{$adjustment->adjustment_number}: {$adjustment->reason}",
                    'performed_by_id' => auth()->id()
                ]);
            }

            // Update adjustment status
            $adjustment->update([
                'status' => 'approved',
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes
            ]);

            DB::commit();

            $adjustment->load(['warehouse', 'adjustedBy', 'approvedBy', 'items.product']);

            return $this->successResponse($adjustment, 'Adjustment approved and inventory updated');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving adjustment', ['error' => $e->getMessage()]);
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Reject adjustment.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $adjustment = Adjustment::find($id);
            if (!$adjustment) {
                return $this->errorResponse('Adjustment not found', 404);
            }

            if ($adjustment->status !== 'pending') {
                return $this->errorResponse('Only pending adjustments can be rejected', 400);
            }

            $adjustment->update([
                'status' => 'rejected',
                'rejected_by_id' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $request->rejection_reason
            ]);

            return $this->successResponse($adjustment, 'Adjustment rejected');
        } catch (\Exception $e) {
            Log::error('Error rejecting adjustment', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to reject adjustment', 500);
        }
    }

    /**
     * Get adjustment statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->warehouse_id;
            $fromDate = $request->from_date ?? now()->startOfMonth();
            $toDate = $request->to_date ?? now()->endOfDay();

            $query = Adjustment::whereBetween('adjustment_date', [$fromDate, $toDate]);
            
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $statistics = [
                'total_adjustments' => $query->count(),
                'by_status' => $query->get()->groupBy('status')->map->count(),
                'by_type' => $query->get()->groupBy('adjustment_type')->map->count(),
                'by_reason' => $query->get()->groupBy('reason')->map->count(),
                'total_value' => [
                    'increase' => $query->where('adjustment_type', 'increase')->sum('total_value'),
                    'decrease' => $query->where('adjustment_type', 'decrease')->sum('total_value')
                ],
                'recent_adjustments' => Adjustment::with(['warehouse', 'adjustedBy'])
                    ->when($warehouseId, function ($q) use ($warehouseId) {
                        $q->where('warehouse_id', $warehouseId);
                    })
                    ->latest('adjustment_date')
                    ->limit(5)
                    ->get()
            ];

            return $this->successResponse($statistics);
        } catch (\Exception $e) {
            Log::error('Error fetching adjustment statistics', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch statistics', 500);
        }
    }

    /**
     * Search for adjustments.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = Adjustment::with(['warehouse', 'adjustedBy']);

            if ($request->has('q')) {
                $search = $request->q;
                $query->where(function ($q) use ($search) {
                    $q->where('adjustment_number', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhere('reason', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $adjustments = $query->limit(10)->get();

            return $this->successResponse($adjustments);
        } catch (\Exception $e) {
            Log::error('Error searching adjustments', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to search adjustments', 500);
        }
    }

    /**
     * Generate adjustment number.
     */
    private function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastAdjustment = Adjustment::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAdjustment) {
            $lastNumber = intval(substr($lastAdjustment->adjustment_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}-{$year}{$month}-{$newNumber}";
    }

    /**
     * Get adjustment reasons.
     */
    public function reasons(): JsonResponse
    {
        $reasons = [
            'damaged' => 'Damaged Goods',
            'expired' => 'Expired Products',
            'lost' => 'Lost/Missing',
            'theft' => 'Theft',
            'count_correction' => 'Count Correction',
            'quality_issue' => 'Quality Issue',
            'system_error' => 'System Error',
            'other' => 'Other'
        ];

        return $this->successResponse($reasons);
    }
}