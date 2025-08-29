<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\BaseApiController;
use Modules\WMSInventoryCore\Models\Warehouse;
use Modules\WMSInventoryCore\Models\WarehouseZone;
use Modules\WMSInventoryCore\Models\BinLocation;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\Transfer;
use Modules\WMSInventoryCore\Models\InventoryTransaction;

class WarehouseController extends BaseApiController
{
    /**
     * Display a listing of warehouses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Warehouse::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by location
            if ($request->has('city')) {
                $query->where('city', 'LIKE', '%' . $request->city . '%');
            }

            if ($request->has('state')) {
                $query->where('state', 'LIKE', '%' . $request->state . '%');
            }

            if ($request->has('country')) {
                $query->where('country', $request->country);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Include relationships
            if ($request->boolean('with_zones')) {
                $query->with('zones');
            }

            if ($request->boolean('with_inventory_count')) {
                $query->withCount('inventory');
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $warehouses = $query->paginate($request->input('per_page', 20));

            return $this->paginatedResponse($warehouses, 'Warehouses retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouses', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created warehouse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:warehouses,name',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'type' => 'required|in:main,branch,distribution,retail,cold_storage,bonded',
            'status' => 'required|in:active,inactive,maintenance',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'manager_name' => 'nullable|string|max:100',
            'operating_hours' => 'nullable|string',
            'total_capacity' => 'nullable|numeric|min:0',
            'capacity_unit' => 'nullable|string|in:sqft,sqm,pallets,units',
            'temperature_controlled' => 'nullable|boolean',
            'security_level' => 'nullable|in:basic,standard,high,maximum',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by_id'] = auth()->id();

            $warehouse = Warehouse::create($data);

            // Create default zone if needed
            if ($request->boolean('create_default_zone')) {
                WarehouseZone::create([
                    'warehouse_id' => $warehouse->id,
                    'name' => 'Default Zone',
                    'code' => 'Z001',
                    'type' => 'storage',
                    'created_by_id' => auth()->id(),
                ]);
            }

            DB::commit();

            return $this->successResponse($warehouse, 'Warehouse created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create warehouse', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified warehouse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::query();

            // Include relationships based on request
            if ($request->boolean('with_zones')) {
                $warehouse->with('zones.binLocations');
            }

            if ($request->boolean('with_inventory')) {
                $warehouse->with(['inventory' => function ($q) {
                    $q->with('product');
                }]);
            }

            if ($request->boolean('with_statistics')) {
                $warehouse->withCount(['inventory', 'zones']);
            }

            $warehouse = $warehouse->findOrFail($id);

            return $this->successResponse($warehouse, 'Warehouse retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified warehouse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:warehouses,name,' . $id,
            'code' => 'sometimes|required|string|max:50|unique:warehouses,code,' . $id,
            'type' => 'sometimes|required|in:main,branch,distribution,retail,cold_storage,bonded',
            'status' => 'sometimes|required|in:active,inactive,maintenance',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'country' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'manager_name' => 'nullable|string|max:100',
            'operating_hours' => 'nullable|string',
            'total_capacity' => 'nullable|numeric|min:0',
            'capacity_unit' => 'nullable|string|in:sqft,sqm,pallets,units',
            'temperature_controlled' => 'nullable|boolean',
            'security_level' => 'nullable|in:basic,standard,high,maximum',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $warehouse = Warehouse::findOrFail($id);

            $data = $request->all();
            $data['updated_by_id'] = auth()->id();

            $warehouse->update($data);

            return $this->successResponse($warehouse, 'Warehouse updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update warehouse', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified warehouse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            // Check if warehouse has inventory
            if ($warehouse->inventory()->exists()) {
                return $this->errorResponse('Cannot delete warehouse with existing inventory', null, 400);
            }

            // Check for pending transfers
            $pendingTransfers = Transfer::where(function ($q) use ($id) {
                    $q->where('from_warehouse_id', $id)
                      ->orWhere('to_warehouse_id', $id);
                })
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();

            if ($pendingTransfers) {
                return $this->errorResponse('Cannot delete warehouse with pending transfers', null, 400);
            }

            $warehouse->delete();

            return $this->successResponse(null, 'Warehouse deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete warehouse', $e->getMessage(), 500);
        }
    }

    /**
     * Get warehouse inventory
     */
    public function inventory(Request $request, $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            $query = $warehouse->inventory()->with('product');

            // Filter by category
            if ($request->has('category_id')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            // Filter by stock status
            if ($request->has('stock_status')) {
                switch ($request->stock_status) {
                    case 'in_stock':
                        $query->where('quantity', '>', 0);
                        break;
                    case 'out_of_stock':
                        $query->where('quantity', '<=', 0);
                        break;
                    case 'low_stock':
                        $query->whereRaw('quantity <= reorder_point');
                        break;
                }
            }

            // Search products
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('sku', 'LIKE', "%{$search}%")
                      ->orWhere('barcode', 'LIKE', "%{$search}%");
                });
            }

            // Include additional data
            if ($request->boolean('with_value')) {
                $query->selectRaw('*, (quantity * cost) as total_value');
            }

            $inventory = $query->paginate($request->input('per_page', 20));

            // Add summary statistics
            if ($request->boolean('with_summary')) {
                $summary = [
                    'total_products' => $warehouse->inventory()->count(),
                    'total_quantity' => $warehouse->inventory()->sum('quantity'),
                    'total_value' => $warehouse->inventory()
                        ->selectRaw('SUM(quantity * cost) as total')
                        ->value('total'),
                    'low_stock_items' => $warehouse->inventory()
                        ->whereRaw('quantity <= reorder_point')
                        ->count(),
                    'out_of_stock_items' => $warehouse->inventory()
                        ->where('quantity', '<=', 0)
                        ->count(),
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'Warehouse inventory retrieved successfully',
                    'data' => $inventory->items(),
                    'summary' => $summary,
                    'pagination' => [
                        'current_page' => $inventory->currentPage(),
                        'per_page' => $inventory->perPage(),
                        'total' => $inventory->total(),
                        'last_page' => $inventory->lastPage(),
                        'has_more_pages' => $inventory->hasMorePages(),
                        'from' => $inventory->firstItem(),
                        'to' => $inventory->lastItem(),
                    ]
                ], 200);
            }

            return $this->paginatedResponse($inventory, 'Warehouse inventory retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse inventory', $e->getMessage(), 500);
        }
    }

    /**
     * Get warehouse zones and bin locations
     */
    public function zones(Request $request, $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            $query = $warehouse->zones();

            // Filter by zone type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Include bin locations
            if ($request->boolean('with_bins')) {
                $query->with('binLocations');
            }

            // Include capacity info
            if ($request->boolean('with_capacity')) {
                $query->withCount('binLocations')
                      ->with(['binLocations' => function ($q) {
                          $q->selectRaw('zone_id, COUNT(*) as total, SUM(CASE WHEN is_occupied = 1 THEN 1 ELSE 0 END) as occupied')
                            ->groupBy('zone_id');
                      }]);
            }

            $zones = $query->get();

            return $this->successResponse($zones, 'Warehouse zones retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse zones', $e->getMessage(), 500);
        }
    }

    /**
     * Get warehouse statistics
     */
    public function statistics(Request $request, $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            // Date range for movements
            $fromDate = $request->input('from_date', now()->subDays(30)->format('Y-m-d'));
            $toDate = $request->input('to_date', now()->format('Y-m-d'));

            $stats = [
                'general' => [
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'type' => $warehouse->type,
                    'status' => $warehouse->status,
                    'total_capacity' => $warehouse->total_capacity,
                    'capacity_unit' => $warehouse->capacity_unit,
                ],
                
                'inventory' => [
                    'total_products' => $warehouse->inventory()->count(),
                    'total_quantity' => $warehouse->inventory()->sum('quantity'),
                    'total_value' => $warehouse->inventory()
                        ->selectRaw('SUM(quantity * cost) as total')
                        ->value('total') ?? 0,
                    'unique_skus' => $warehouse->inventory()->distinct('product_id')->count(),
                    'categories' => $warehouse->inventory()
                        ->join('products', 'inventories.product_id', '=', 'products.id')
                        ->join('categories', 'products.category_id', '=', 'categories.id')
                        ->select('categories.name', DB::raw('COUNT(DISTINCT products.id) as product_count'))
                        ->groupBy('categories.id', 'categories.name')
                        ->get(),
                ],
                
                'stock_status' => [
                    'in_stock' => $warehouse->inventory()->where('quantity', '>', 0)->count(),
                    'out_of_stock' => $warehouse->inventory()->where('quantity', '<=', 0)->count(),
                    'low_stock' => $warehouse->inventory()->whereRaw('quantity <= reorder_point')->count(),
                    'overstock' => $warehouse->inventory()->whereRaw('quantity > maximum_stock')->count(),
                ],
                
                'movements' => [
                    'period' => "{$fromDate} to {$toDate}",
                    'total_inbound' => InventoryTransaction::where('warehouse_id', $id)
                        ->whereIn('type', ['purchase', 'transfer_in', 'adjustment_increase', 'return'])
                        ->whereBetween('created_at', [$fromDate, $toDate])
                        ->sum('quantity'),
                    'total_outbound' => InventoryTransaction::where('warehouse_id', $id)
                        ->whereIn('type', ['sale', 'transfer_out', 'adjustment_decrease'])
                        ->whereBetween('created_at', [$fromDate, $toDate])
                        ->sum('quantity'),
                    'transactions_count' => InventoryTransaction::where('warehouse_id', $id)
                        ->whereBetween('created_at', [$fromDate, $toDate])
                        ->count(),
                ],
                
                'zones' => [
                    'total_zones' => $warehouse->zones()->count(),
                    'total_bin_locations' => BinLocation::whereHas('zone', function ($q) use ($id) {
                        $q->where('warehouse_id', $id);
                    })->count(),
                    'occupied_bins' => BinLocation::whereHas('zone', function ($q) use ($id) {
                        $q->where('warehouse_id', $id);
                    })->where('is_occupied', true)->count(),
                    'zone_breakdown' => $warehouse->zones()
                        ->select('type', DB::raw('COUNT(*) as count'))
                        ->groupBy('type')
                        ->get(),
                ],
                
                'performance' => [
                    'avg_daily_transactions' => InventoryTransaction::where('warehouse_id', $id)
                        ->whereBetween('created_at', [$fromDate, $toDate])
                        ->count() / max(1, now()->parse($fromDate)->diffInDays(now()->parse($toDate))),
                    'top_moving_products' => InventoryTransaction::where('warehouse_id', $id)
                        ->whereBetween('created_at', [$fromDate, $toDate])
                        ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
                        ->select('products.name', 'products.sku', DB::raw('SUM(ABS(inventory_transactions.quantity)) as total_movement'))
                        ->groupBy('products.id', 'products.name', 'products.sku')
                        ->orderBy('total_movement', 'desc')
                        ->limit(10)
                        ->get(),
                ],
            ];

            // Calculate capacity utilization if available
            if ($warehouse->total_capacity && $warehouse->capacity_unit) {
                $utilization = $this->calculateCapacityUtilization($warehouse);
                $stats['capacity_utilization'] = $utilization;
            }

            return $this->successResponse($stats, 'Warehouse statistics retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve warehouse statistics', $e->getMessage(), 500);
        }
    }

    /**
     * Get warehouse stock valuation
     */
    public function stockValuation(Request $request, $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            $valuation = $warehouse->inventory()
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'categories.name as category',
                    DB::raw('COUNT(DISTINCT products.id) as product_count'),
                    DB::raw('SUM(inventories.quantity) as total_quantity'),
                    DB::raw('SUM(inventories.quantity * inventories.cost) as cost_value'),
                    DB::raw('SUM(inventories.quantity * products.price) as retail_value'),
                    DB::raw('SUM(inventories.quantity * products.price) - SUM(inventories.quantity * inventories.cost) as potential_profit')
                )
                ->groupBy('categories.id', 'categories.name')
                ->get();

            $summary = [
                'total_cost_value' => $valuation->sum('cost_value'),
                'total_retail_value' => $valuation->sum('retail_value'),
                'potential_profit' => $valuation->sum('potential_profit'),
                'profit_margin' => $valuation->sum('cost_value') > 0 
                    ? ($valuation->sum('potential_profit') / $valuation->sum('cost_value')) * 100 
                    : 0,
                'categories' => $valuation,
            ];

            return $this->successResponse($summary, 'Stock valuation retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve stock valuation', $e->getMessage(), 500);
        }
    }

    /**
     * Get transfer history for warehouse
     */
    public function transferHistory(Request $request, $id): JsonResponse
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            $query = Transfer::where(function ($q) use ($id) {
                $q->where('from_warehouse_id', $id)
                  ->orWhere('to_warehouse_id', $id);
            })->with(['fromWarehouse', 'toWarehouse', 'transferProducts.product']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type (inbound/outbound)
            if ($request->has('type')) {
                if ($request->type === 'inbound') {
                    $query->where('to_warehouse_id', $id);
                } elseif ($request->type === 'outbound') {
                    $query->where('from_warehouse_id', $id);
                }
            }

            // Date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $transfers = $query->paginate($request->input('per_page', 20));

            return $this->paginatedResponse($transfers, 'Transfer history retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Warehouse not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve transfer history', $e->getMessage(), 500);
        }
    }

    /**
     * Calculate capacity utilization for warehouse
     */
    private function calculateCapacityUtilization($warehouse): array
    {
        $totalBins = BinLocation::whereHas('zone', function ($q) use ($warehouse) {
            $q->where('warehouse_id', $warehouse->id);
        })->count();

        $occupiedBins = BinLocation::whereHas('zone', function ($q) use ($warehouse) {
            $q->where('warehouse_id', $warehouse->id);
        })->where('is_occupied', true)->count();

        $utilizationPercent = $totalBins > 0 ? ($occupiedBins / $totalBins) * 100 : 0;

        return [
            'total_capacity' => $warehouse->total_capacity,
            'capacity_unit' => $warehouse->capacity_unit,
            'total_bins' => $totalBins,
            'occupied_bins' => $occupiedBins,
            'available_bins' => $totalBins - $occupiedBins,
            'utilization_percent' => round($utilizationPercent, 2),
            'status' => $this->getUtilizationStatus($utilizationPercent),
        ];
    }

    /**
     * Get utilization status based on percentage
     */
    private function getUtilizationStatus($percent): string
    {
        if ($percent >= 95) return 'critical';
        if ($percent >= 85) return 'high';
        if ($percent >= 70) return 'optimal';
        if ($percent >= 40) return 'moderate';
        return 'low';
    }
}