<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\BaseApiController;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\StockAlert;
use Modules\WMSInventoryCore\Models\Warehouse;

class InventoryController extends BaseApiController
{
    /**
     * Display inventory listing.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Inventory::with(['product', 'product.category', 'product.unit', 'warehouse']);

            // Filters
            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('category_id')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            if ($request->has('low_stock')) {
                $query->whereRaw('quantity <= reorder_point');
            }

            if ($request->has('out_of_stock')) {
                $query->where('quantity', '<=', 0);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            // Include stock value
            if ($request->boolean('include_value')) {
                $query->selectRaw('*, (quantity * COALESCE((SELECT unit_cost FROM products WHERE products.id = inventories.product_id), 0)) as stock_value');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'product_id');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $inventory = $query->paginate($perPage);

            return $this->paginatedResponse($inventory, 'Inventory retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error fetching inventory', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch inventory', 500);
        }
    }

    /**
     * Get inventory details for specific product and warehouse.
     */
    public function show($productId, $warehouseId = null): JsonResponse
    {
        try {
            $query = Inventory::with(['product', 'warehouse'])
                ->where('product_id', $productId);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
                $inventory = $query->first();
            } else {
                $inventory = $query->get();
            }

            if (! $inventory || ($warehouseId && ! $inventory)) {
                return $this->errorResponse('Inventory not found', 404);
            }

            return $this->successResponse($inventory);
        } catch (\Exception $e) {
            Log::error('Error fetching inventory details', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch inventory details', 500);
        }
    }

    /**
     * Update inventory settings (reorder point, reserved quantity, etc.).
     */
    public function update(Request $request, $productId, $warehouseId): JsonResponse
    {
        try {
            $request->validate([
                'reorder_point' => 'sometimes|numeric|min:0',
                'reorder_quantity' => 'sometimes|numeric|min:0',
                'max_stock' => 'sometimes|numeric|min:0',
                'min_stock' => 'sometimes|numeric|min:0',
                'reserved_quantity' => 'sometimes|numeric|min:0',
            ]);

            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (! $inventory) {
                return $this->errorResponse('Inventory not found', 404);
            }

            // Validate reserved quantity doesn't exceed available
            if ($request->has('reserved_quantity')) {
                $availableQuantity = $inventory->quantity - $inventory->reserved_quantity;
                $newReserved = $request->reserved_quantity;

                if ($newReserved > $inventory->quantity) {
                    return $this->errorResponse('Reserved quantity cannot exceed total quantity', 400);
                }
            }

            $inventory->update($request->only([
                'reorder_point',
                'reorder_quantity',
                'max_stock',
                'min_stock',
                'reserved_quantity',
            ]));

            $inventory->update(['last_updated' => now()]);

            return $this->successResponse($inventory, 'Inventory settings updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating inventory', ['error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get stock levels across all warehouses for a product.
     */
    public function stockLevels($productId): JsonResponse
    {
        try {
            $product = Product::find($productId);
            if (! $product) {
                return $this->errorResponse('Product not found', 404);
            }

            $stockLevels = Inventory::with('warehouse')
                ->where('product_id', $productId)
                ->get()
                ->map(function ($inventory) {
                    return [
                        'warehouse' => $inventory->warehouse,
                        'quantity' => $inventory->quantity,
                        'reserved_quantity' => $inventory->reserved_quantity,
                        'available_quantity' => $inventory->quantity - $inventory->reserved_quantity,
                        'reorder_point' => $inventory->reorder_point,
                        'status' => $this->getStockStatus($inventory),
                        'last_updated' => $inventory->last_updated,
                    ];
                });

            $summary = [
                'product' => $product,
                'total_quantity' => $stockLevels->sum('quantity'),
                'total_reserved' => $stockLevels->sum('reserved_quantity'),
                'total_available' => $stockLevels->sum('available_quantity'),
                'warehouses' => $stockLevels,
            ];

            return $this->successResponse($summary);
        } catch (\Exception $e) {
            Log::error('Error fetching stock levels', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch stock levels', 500);
        }
    }

    /**
     * Get inventory transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $query = InventoryTransaction::with(['product', 'warehouse', 'performedBy']);

            // Filters
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            if ($request->has('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            if ($request->has('movement_type')) {
                $query->where('movement_type', $request->movement_type);
            }

            if ($request->has('from_date')) {
                $query->whereDate('transaction_date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('transaction_date', '<=', $request->to_date);
            }

            if ($request->has('reference_type')) {
                $query->where('reference_type', $request->reference_type);
            }

            if ($request->has('reference_id')) {
                $query->where('reference_id', $request->reference_id);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $transactions = $query->paginate($perPage);

            return $this->paginatedResponse($transactions, 'Transactions retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error fetching transactions', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch transactions', 500);
        }
    }

    /**
     * Get low stock items.
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $query = Inventory::with(['product', 'product.category', 'warehouse'])
                ->whereRaw('quantity <= reorder_point')
                ->where('reorder_point', '>', 0);

            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            $lowStockItems = $query->get()->map(function ($inventory) {
                return [
                    'product' => $inventory->product,
                    'warehouse' => $inventory->warehouse,
                    'current_quantity' => $inventory->quantity,
                    'reorder_point' => $inventory->reorder_point,
                    'reorder_quantity' => $inventory->reorder_quantity,
                    'shortage' => $inventory->reorder_point - $inventory->quantity,
                    'estimated_value' => ($inventory->reorder_point - $inventory->quantity) * ($inventory->product->unit_cost ?? 0),
                ];
            });

            $summary = [
                'total_items' => $lowStockItems->count(),
                'total_shortage' => $lowStockItems->sum('shortage'),
                'estimated_reorder_value' => $lowStockItems->sum('estimated_value'),
                'items' => $lowStockItems,
            ];

            return $this->successResponse($summary);
        } catch (\Exception $e) {
            Log::error('Error fetching low stock items', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch low stock items', 500);
        }
    }

    /**
     * Get out of stock items.
     */
    public function outOfStock(Request $request): JsonResponse
    {
        try {
            $query = Inventory::with(['product', 'product.category', 'warehouse'])
                ->where('quantity', '<=', 0);

            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            $outOfStockItems = $query->get();

            return $this->successResponse($outOfStockItems);
        } catch (\Exception $e) {
            Log::error('Error fetching out of stock items', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch out of stock items', 500);
        }
    }

    /**
     * Get inventory valuation.
     */
    public function valuation(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->warehouse_id;
            $categoryId = $request->category_id;

            $query = Inventory::with(['product', 'product.category', 'warehouse']);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            if ($categoryId) {
                $query->whereHas('product', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }

            $inventory = $query->get();

            $valuation = $inventory->map(function ($item) {
                $unitCost = $item->product->unit_cost ?? 0;
                $totalValue = $item->quantity * $unitCost;

                return [
                    'product' => $item->product->name,
                    'sku' => $item->product->sku,
                    'category' => $item->product->category->name ?? 'Uncategorized',
                    'warehouse' => $item->warehouse->name,
                    'quantity' => $item->quantity,
                    'unit_cost' => $unitCost,
                    'total_value' => $totalValue,
                ];
            });

            $summary = [
                'total_items' => $valuation->count(),
                'total_quantity' => $valuation->sum('quantity'),
                'total_value' => $valuation->sum('total_value'),
                'by_category' => $valuation->groupBy('category')->map(function ($items, $category) {
                    return [
                        'quantity' => $items->sum('quantity'),
                        'value' => $items->sum('total_value'),
                    ];
                }),
                'by_warehouse' => $valuation->groupBy('warehouse')->map(function ($items, $warehouse) {
                    return [
                        'quantity' => $items->sum('quantity'),
                        'value' => $items->sum('total_value'),
                    ];
                }),
                'items' => $valuation,
            ];

            return $this->successResponse($summary);
        } catch (\Exception $e) {
            Log::error('Error calculating inventory valuation', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to calculate valuation', 500);
        }
    }

    /**
     * Reserve inventory for an order or operation.
     */
    public function reserve(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'quantity' => 'required|numeric|min:1',
                'reference_type' => 'required|string',
                'reference_id' => 'required|integer',
                'notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $inventory = Inventory::where('product_id', $request->product_id)
                ->where('warehouse_id', $request->warehouse_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \Exception('Inventory not found');
            }

            $availableQuantity = $inventory->quantity - $inventory->reserved_quantity;

            if ($request->quantity > $availableQuantity) {
                throw new \Exception("Insufficient available quantity. Available: {$availableQuantity}, Requested: {$request->quantity}");
            }

            // Update reserved quantity
            $inventory->update([
                'reserved_quantity' => $inventory->reserved_quantity + $request->quantity,
                'last_updated' => now(),
            ]);

            // Create transaction record
            InventoryTransaction::create([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'transaction_type' => 'reservation',
                'transaction_date' => now(),
                'quantity' => $request->quantity,
                'movement_type' => 'reserve',
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'balance_after' => $inventory->quantity,
                'notes' => $request->notes ?? "Reserved {$request->quantity} units",
                'performed_by_id' => auth()->id(),
            ]);

            DB::commit();

            return $this->successResponse($inventory, 'Inventory reserved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reserving inventory', ['error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Release reserved inventory.
     */
    public function release(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'quantity' => 'required|numeric|min:1',
                'reference_type' => 'required|string',
                'reference_id' => 'required|integer',
                'notes' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $inventory = Inventory::where('product_id', $request->product_id)
                ->where('warehouse_id', $request->warehouse_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \Exception('Inventory not found');
            }

            if ($request->quantity > $inventory->reserved_quantity) {
                throw new \Exception("Cannot release more than reserved. Reserved: {$inventory->reserved_quantity}, Requested: {$request->quantity}");
            }

            // Update reserved quantity
            $inventory->update([
                'reserved_quantity' => $inventory->reserved_quantity - $request->quantity,
                'last_updated' => now(),
            ]);

            // Create transaction record
            InventoryTransaction::create([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'transaction_type' => 'release',
                'transaction_date' => now(),
                'quantity' => $request->quantity,
                'movement_type' => 'release',
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'balance_after' => $inventory->quantity,
                'notes' => $request->notes ?? "Released {$request->quantity} units",
                'performed_by_id' => auth()->id(),
            ]);

            DB::commit();

            return $this->successResponse($inventory, 'Inventory released successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error releasing inventory', ['error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get stock alerts.
     */
    public function alerts(Request $request): JsonResponse
    {
        try {
            $query = StockAlert::with(['product', 'warehouse'])
                ->where('status', 'active');

            if ($request->has('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            if ($request->has('alert_type')) {
                $query->where('alert_type', $request->alert_type);
            }

            $alerts = $query->latest()->get();

            return $this->successResponse($alerts);
        } catch (\Exception $e) {
            Log::error('Error fetching stock alerts', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch alerts', 500);
        }
    }

    /**
     * Get inventory statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->warehouse_id;

            $query = Inventory::query();
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $totalProducts = $query->count();
            $totalQuantity = $query->sum('quantity');
            $totalReserved = $query->sum('reserved_quantity');
            $lowStockCount = $query->whereRaw('quantity <= reorder_point')->where('reorder_point', '>', 0)->count();
            $outOfStockCount = $query->where('quantity', '<=', 0)->count();

            // Calculate total value
            $totalValue = $query->join('products', 'inventories.product_id', '=', 'products.id')
                ->selectRaw('SUM(inventories.quantity * COALESCE(products.unit_cost, 0)) as total_value')
                ->value('total_value');

            $statistics = [
                'total_products' => $totalProducts,
                'total_quantity' => $totalQuantity,
                'total_reserved' => $totalReserved,
                'total_available' => $totalQuantity - $totalReserved,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'total_value' => $totalValue ?? 0,
                'stock_health' => [
                    'healthy' => $totalProducts - $lowStockCount - $outOfStockCount,
                    'low' => $lowStockCount,
                    'out' => $outOfStockCount,
                ],
            ];

            return $this->successResponse($statistics);
        } catch (\Exception $e) {
            Log::error('Error fetching inventory statistics', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch statistics', 500);
        }
    }

    /**
     * Get stock status.
     */
    private function getStockStatus($inventory): string
    {
        if ($inventory->quantity <= 0) {
            return 'out_of_stock';
        } elseif ($inventory->quantity <= $inventory->reorder_point) {
            return 'low_stock';
        } elseif ($inventory->max_stock && $inventory->quantity >= $inventory->max_stock) {
            return 'overstock';
        } else {
            return 'in_stock';
        }
    }
}
