<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\BaseApiController;
use Modules\WMSInventoryCore\app\Http\Requests\BatchProductRequest;
use Modules\WMSInventoryCore\app\Http\Requests\StoreProductRequest;
use Modules\WMSInventoryCore\app\Http\Requests\UpdateProductRequest;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Modules\WMSInventoryCore\Models\Product;

class ProductController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'unit_id' => 'nullable|exists:units,id',
                'status' => 'nullable|in:active,inactive,discontinued',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'low_stock' => 'nullable|boolean',
                'sort_by' => 'nullable|in:name,code,sku,created_at,updated_at,cost_price,selling_price',
                'sort_order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
                'with_inventory' => 'nullable|boolean',
                'with_categories' => 'nullable|boolean',
                'with_units' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $query = Product::query();

            // Apply search
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%")
                        ->orWhere('barcode', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            if ($request->filled('unit_id')) {
                $query->where('unit_id', $request->get('unit_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by warehouse stock
            if ($request->filled('warehouse_id')) {
                $query->whereHas('inventories', function (Builder $q) use ($request) {
                    $q->where('warehouse_id', $request->get('warehouse_id'));
                });
            }

            // Filter low stock products
            if ($request->boolean('low_stock')) {
                $query->whereHas('inventories', function (Builder $q) {
                    $q->whereRaw('stock_level <= COALESCE(products.reorder_point, products.min_stock_level, 0)');
                });
            }

            // Apply relationships
            $with = ['createdBy', 'updatedBy'];

            if ($request->boolean('with_inventory')) {
                $with[] = 'inventories.warehouse';
            }

            if ($request->boolean('with_categories')) {
                $with[] = 'category';
            }

            if ($request->boolean('with_units')) {
                $with[] = 'unit';
            }

            $query->with($with);

            // Apply sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return $this->paginatedResponse(
                $products,
                'Products retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['created_by_id'] = auth()->id();

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('products', 'public');
                $data['image'] = $imagePath;
            }

            $product = Product::create($data);

            // Load relationships for response
            $product->load(['category', 'unit', 'createdBy']);

            DB::commit();

            return $this->successResponse(
                $product,
                'Product created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded image if it exists
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return $this->serverErrorResponse('Failed to create product: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     */
    public function show($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'with_inventory' => 'nullable|boolean',
                'with_transactions' => 'nullable|boolean',
                'with_batches' => 'nullable|boolean',
                'with_bin_locations' => 'nullable|boolean',
                'with_prices' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $with = ['category', 'unit', 'createdBy', 'updatedBy'];

            if ($request->boolean('with_inventory')) {
                $with[] = 'inventories.warehouse';
            }

            if ($request->boolean('with_transactions')) {
                $with[] = 'inventoryTransactions';
            }

            if ($request->boolean('with_batches')) {
                $with[] = 'batches';
            }

            if ($request->boolean('with_bin_locations')) {
                $with[] = 'binLocations.binLocation';
            }

            if ($request->boolean('with_prices')) {
                $with[] = 'prices';
            }

            $product = Product::with($with)->findOrFail($id);

            return $this->successResponse(
                $product,
                'Product retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve product: '.$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     */
    public function update(UpdateProductRequest $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);
            $data = $request->validated();
            $data['updated_by_id'] = auth()->id();

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }

                $image = $request->file('image');
                $imagePath = $image->store('products', 'public');
                $data['image'] = $imagePath;
            }

            $product->update($data);

            // Load relationships for response
            $product->load(['category', 'unit', 'updatedBy']);

            DB::commit();

            return $this->successResponse(
                $product,
                'Product updated successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded image if it exists
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return $this->serverErrorResponse('Failed to update product: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);

            // Check if product has inventory
            $hasInventory = $product->inventories()->where('stock_level', '>', 0)->exists();
            if ($hasInventory) {
                return $this->errorResponse(
                    'Cannot delete product with existing stock. Please adjust inventory first.',
                    null,
                    422
                );
            }

            // Delete associated image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            DB::commit();

            return $this->successResponse(
                null,
                'Product deleted successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverErrorResponse('Failed to delete product: '.$e->getMessage());
        }
    }

    /**
     * Search products.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:1|max:255',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'category_id' => 'nullable|exists:categories,id',
                'status' => 'nullable|in:active,inactive,discontinued',
                'limit' => 'nullable|integer|min:1|max:50',
                'with_stock_only' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $query = Product::query();
            $searchTerm = $request->get('query');
            $limit = $request->get('limit', 20);

            // Apply search across multiple fields
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('code', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('sku', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('barcode', 'LIKE', "%{$searchTerm}%")
                    ->orWhereJsonContains('additional_barcodes', $searchTerm);
            });

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by warehouse stock
            if ($request->filled('warehouse_id')) {
                $query->whereHas('inventories', function (Builder $q) use ($request) {
                    $q->where('warehouse_id', $request->get('warehouse_id'));

                    if ($request->boolean('with_stock_only')) {
                        $q->where('stock_level', '>', 0);
                    }
                });
            }

            // Load relationships
            $query->with(['category', 'unit']);

            // If warehouse filter is applied, also load inventory for that warehouse
            if ($request->filled('warehouse_id')) {
                $warehouseId = $request->get('warehouse_id');
                $query->with(['inventories' => function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }]);
            }

            $products = $query->limit($limit)->get();

            return $this->successResponse(
                $products,
                'Products search completed successfully'
            );

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Search failed: '.$e->getMessage());
        }
    }

    /**
     * Get product inventory by warehouse.
     *
     * @param  int  $id
     */
    public function inventory($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'with_transactions' => 'nullable|boolean',
                'with_batches' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $product = Product::findOrFail($id);
            $query = $product->inventories()->with(['warehouse', 'unit']);

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->get('warehouse_id'));
            }

            if ($request->boolean('with_transactions')) {
                $query->with('inventoryTransactions');
            }

            if ($request->boolean('with_batches')) {
                $query->with('batches');
            }

            $inventories = $query->get();

            // Add computed fields
            $inventories->each(function ($inventory) {
                $inventory->available_quantity = $inventory->stock_level - $inventory->reserved_quantity;
                $inventory->total_value = $inventory->stock_level * $inventory->cost_price;
                $inventory->health_status = $inventory->health_status;
                $inventory->quality_status = $inventory->quality_status;
            });

            return $this->successResponse([
                'product' => $product->only(['id', 'name', 'code', 'sku']),
                'inventories' => $inventories,
            ], 'Product inventory retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve inventory: '.$e->getMessage());
        }
    }

    /**
     * Get product stock levels across all warehouses.
     *
     * @param  int  $id
     */
    public function stockLevels($id): JsonResponse
    {
        try {
            $product = Product::with(['inventories.warehouse', 'unit'])->findOrFail($id);

            $stockData = [
                'product' => $product->only(['id', 'name', 'code', 'sku', 'reorder_point', 'min_stock_level', 'max_stock_level']),
                'total_stock' => 0,
                'total_reserved' => 0,
                'total_available' => 0,
                'total_value' => 0,
                'warehouses' => [],
                'is_low_stock' => false,
                'status' => 'healthy',
            ];

            foreach ($product->inventories as $inventory) {
                $available = $inventory->stock_level - $inventory->reserved_quantity;
                $value = $inventory->stock_level * $inventory->cost_price;

                $stockData['total_stock'] += $inventory->stock_level;
                $stockData['total_reserved'] += $inventory->reserved_quantity;
                $stockData['total_available'] += $available;
                $stockData['total_value'] += $value;

                $stockData['warehouses'][] = [
                    'warehouse_id' => $inventory->warehouse_id,
                    'warehouse_name' => $inventory->warehouse->name,
                    'stock_level' => $inventory->stock_level,
                    'reserved_quantity' => $inventory->reserved_quantity,
                    'available_quantity' => $available,
                    'damaged_quantity' => $inventory->damaged_quantity,
                    'quarantine_quantity' => $inventory->quarantine_quantity,
                    'in_transit_quantity' => $inventory->in_transit_quantity,
                    'cost_price' => $inventory->cost_price,
                    'total_value' => $value,
                    'health_status' => $inventory->health_status,
                    'quality_status' => $inventory->quality_status,
                    'last_movement_date' => $inventory->last_movement_date,
                    'last_count_date' => $inventory->last_count_date,
                ];
            }

            // Determine overall status
            if ($product->reorder_point && $stockData['total_available'] <= $product->reorder_point) {
                $stockData['is_low_stock'] = true;
                $stockData['status'] = 'reorder';
            } elseif ($product->min_stock_level && $stockData['total_available'] <= $product->min_stock_level) {
                $stockData['is_low_stock'] = true;
                $stockData['status'] = 'low';
            } elseif ($product->max_stock_level && $stockData['total_available'] >= $product->max_stock_level) {
                $stockData['status'] = 'overstocked';
            }

            return $this->successResponse(
                $stockData,
                'Product stock levels retrieved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve stock levels: '.$e->getMessage());
        }
    }

    /**
     * Get product movements/transactions.
     *
     * @param  int  $id
     */
    public function movements($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'transaction_type' => 'nullable|string|max:50',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|in:created_at,quantity,transaction_type',
                'sort_order' => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $product = Product::findOrFail($id);

            $query = InventoryTransaction::with(['warehouse', 'unit', 'createdBy'])
                ->where('product_id', $id);

            // Apply filters
            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->get('warehouse_id'));
            }

            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->get('transaction_type'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->get('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->get('date_to'));
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = $request->get('per_page', 20);
            $movements = $query->paginate($perPage);

            return $this->successResponse([
                'product' => $product->only(['id', 'name', 'code', 'sku']),
                'movements' => $movements,
            ], 'Product movements retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Product not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve movements: '.$e->getMessage());
        }
    }

    /**
     * Batch operations on multiple products.
     */
    public function batchOperation(BatchProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $operation = $request->get('operation');
            $productIds = $request->get('product_ids');
            $affectedProducts = 0;

            $products = Product::whereIn('id', $productIds);

            switch ($operation) {
                case 'delete':
                    // Check for products with stock
                    $productsWithStock = Product::whereIn('id', $productIds)
                        ->whereHas('inventories', function (Builder $q) {
                            $q->where('stock_level', '>', 0);
                        })->count();

                    if ($productsWithStock > 0) {
                        return $this->errorResponse(
                            "Cannot delete {$productsWithStock} products with existing stock",
                            null,
                            422
                        );
                    }

                    $affectedProducts = $products->count();
                    $products->delete();
                    break;

                case 'update_status':
                    $affectedProducts = $products->update([
                        'status' => $request->get('status'),
                        'updated_by_id' => auth()->id(),
                    ]);
                    break;

                case 'update_category':
                    $affectedProducts = $products->update([
                        'category_id' => $request->get('category_id'),
                        'updated_by_id' => auth()->id(),
                    ]);
                    break;

                case 'update_unit':
                    $affectedProducts = $products->update([
                        'unit_id' => $request->get('unit_id'),
                        'updated_by_id' => auth()->id(),
                    ]);
                    break;

                case 'update_prices':
                    $updateData = [
                        'updated_by_id' => auth()->id(),
                    ];

                    if ($request->filled('price_adjustment_type') && $request->filled('price_adjustment_value')) {
                        // Apply price adjustment
                        $adjustmentType = $request->get('price_adjustment_type');
                        $adjustmentValue = $request->get('price_adjustment_value');

                        if ($adjustmentType === 'percentage') {
                            $products->chunk(100, function ($productChunk) use ($adjustmentValue) {
                                foreach ($productChunk as $product) {
                                    $product->update([
                                        'cost_price' => $product->cost_price * (1 + $adjustmentValue / 100),
                                        'selling_price' => $product->selling_price * (1 + $adjustmentValue / 100),
                                        'updated_by_id' => auth()->id(),
                                    ]);
                                }
                            });
                        } else {
                            $products->chunk(100, function ($productChunk) use ($adjustmentValue) {
                                foreach ($productChunk as $product) {
                                    $product->update([
                                        'cost_price' => $product->cost_price + $adjustmentValue,
                                        'selling_price' => $product->selling_price + $adjustmentValue,
                                        'updated_by_id' => auth()->id(),
                                    ]);
                                }
                            });
                        }
                        $affectedProducts = count($productIds);
                    } else {
                        // Set fixed prices
                        $updateData['cost_price'] = $request->get('cost_price');
                        $updateData['selling_price'] = $request->get('selling_price');
                        $affectedProducts = $products->update($updateData);
                    }
                    break;

                default:
                    return $this->errorResponse('Invalid batch operation', null, 422);
            }

            DB::commit();

            return $this->successResponse([
                'operation' => $operation,
                'affected_products' => $affectedProducts,
                'product_ids' => $productIds,
            ], "Batch operation completed successfully. {$affectedProducts} products affected.");

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverErrorResponse('Batch operation failed: '.$e->getMessage());
        }
    }

    /**
     * Lookup product by barcode.
     */
    public function barcodeLookup(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'barcode' => 'required|string|max:100',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'with_inventory' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $barcode = $request->get('barcode');

            // Search in primary barcode field and additional_barcodes JSON field
            $product = Product::where('barcode', $barcode)
                ->orWhereJsonContains('additional_barcodes', $barcode)
                ->with(['category', 'unit'])
                ->first();

            if (! $product) {
                return $this->notFoundResponse('Product not found for the given barcode');
            }

            $response = $product->toArray();

            // Add inventory information if requested
            if ($request->boolean('with_inventory')) {
                $inventoryQuery = $product->inventories()->with('warehouse');

                if ($request->filled('warehouse_id')) {
                    $inventoryQuery->where('warehouse_id', $request->get('warehouse_id'));
                }

                $inventories = $inventoryQuery->get();

                // Add computed fields
                $inventories->each(function ($inventory) {
                    $inventory->available_quantity = $inventory->stock_level - $inventory->reserved_quantity;
                    $inventory->total_value = $inventory->stock_level * $inventory->cost_price;
                    $inventory->health_status = $inventory->health_status;
                    $inventory->quality_status = $inventory->quality_status;
                });

                $response['inventories'] = $inventories;
            }

            return $this->successResponse(
                $response,
                'Product found successfully'
            );

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Barcode lookup failed: '.$e->getMessage());
        }
    }

    /**
     * Get products that need reordering.
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'category_id' => 'nullable|exists:categories,id',
                'sort_by' => 'nullable|in:name,reorder_point,stock_level,urgency',
                'sort_order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $query = Product::with(['category', 'unit', 'inventories.warehouse'])
                ->whereHas('inventories', function (Builder $q) use ($request) {
                    if ($request->filled('warehouse_id')) {
                        $q->where('warehouse_id', $request->get('warehouse_id'));
                    }

                    // Products below reorder point or min stock level
                    $q->whereRaw('stock_level <= COALESCE(products.reorder_point, products.min_stock_level, 0)');
                });

            // Apply category filter
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');

            if ($sortBy === 'urgency') {
                // Custom sorting by urgency (percentage below reorder point)
                $query->orderByRaw('
                    CASE 
                        WHEN products.reorder_point > 0 THEN 
                            (SELECT AVG(stock_level / products.reorder_point) FROM inventories WHERE product_id = products.id)
                        ELSE 1
                    END '.$sortOrder
                );
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            // Add computed fields for each product
            $products->getCollection()->transform(function ($product) {
                $totalStock = $product->inventories->sum('stock_level');
                $totalAvailable = $product->inventories->sum(function ($inv) {
                    return $inv->stock_level - $inv->reserved_quantity;
                });

                $product->total_stock = $totalStock;
                $product->total_available = $totalAvailable;
                $product->shortage_amount = max(0, ($product->reorder_point ?: $product->min_stock_level ?: 0) - $totalAvailable);
                $product->urgency_level = $this->calculateUrgencyLevel($product, $totalAvailable);

                return $product;
            });

            return $this->successResponse(
                $products,
                'Low stock products retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve low stock products: '.$e->getMessage());
        }
    }

    /**
     * Get product statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'category_id' => 'nullable|exists:categories,id',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $baseQuery = Product::query();
            $inventoryQuery = Inventory::query();

            // Apply filters
            if ($request->filled('category_id')) {
                $baseQuery->where('category_id', $request->get('category_id'));
                $inventoryQuery->whereHas('product', function (Builder $q) use ($request) {
                    $q->where('category_id', $request->get('category_id'));
                });
            }

            if ($request->filled('warehouse_id')) {
                $inventoryQuery->where('warehouse_id', $request->get('warehouse_id'));
            }

            // Basic product statistics
            $stats = [
                'total_products' => (clone $baseQuery)->count(),
                'active_products' => (clone $baseQuery)->where('status', 'active')->count(),
                'inactive_products' => (clone $baseQuery)->where('status', 'inactive')->count(),
                'discontinued_products' => (clone $baseQuery)->where('status', 'discontinued')->count(),

                // Stock statistics
                'total_stock_value' => $inventoryQuery->selectRaw('SUM(stock_level * cost_price)')->value('SUM(stock_level * cost_price)') ?: 0,
                'total_stock_quantity' => $inventoryQuery->sum('stock_level') ?: 0,
                'total_reserved_quantity' => $inventoryQuery->sum('reserved_quantity') ?: 0,
                'total_available_quantity' => $inventoryQuery->selectRaw('SUM(stock_level - reserved_quantity)')->value('SUM(stock_level - reserved_quantity)') ?: 0,

                // Quality statistics
                'total_damaged_quantity' => $inventoryQuery->sum('damaged_quantity') ?: 0,
                'total_quarantine_quantity' => $inventoryQuery->sum('quarantine_quantity') ?: 0,
                'total_in_transit_quantity' => $inventoryQuery->sum('in_transit_quantity') ?: 0,
            ];

            // Low stock statistics
            $lowStockQuery = (clone $baseQuery)->whereHas('inventories', function (Builder $q) use ($request) {
                if ($request->filled('warehouse_id')) {
                    $q->where('warehouse_id', $request->get('warehouse_id'));
                }
                $q->whereRaw('stock_level <= COALESCE(products.reorder_point, products.min_stock_level, 0)');
            });

            $stats['low_stock_products'] = $lowStockQuery->count();
            $stats['reorder_needed_products'] = (clone $baseQuery)
                ->whereHas('inventories', function (Builder $q) use ($request) {
                    if ($request->filled('warehouse_id')) {
                        $q->where('warehouse_id', $request->get('warehouse_id'));
                    }
                    $q->whereRaw('stock_level <= products.reorder_point AND products.reorder_point IS NOT NULL');
                })->count();

            // Movement statistics (if date range provided)
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $movementQuery = InventoryTransaction::query();

                if ($request->filled('warehouse_id')) {
                    $movementQuery->where('warehouse_id', $request->get('warehouse_id'));
                }

                if ($request->filled('category_id')) {
                    $movementQuery->whereHas('product', function (Builder $q) use ($request) {
                        $q->where('category_id', $request->get('category_id'));
                    });
                }

                if ($request->filled('date_from')) {
                    $movementQuery->whereDate('created_at', '>=', $request->get('date_from'));
                }

                if ($request->filled('date_to')) {
                    $movementQuery->whereDate('created_at', '<=', $request->get('date_to'));
                }

                $stats['period_movements'] = [
                    'total_transactions' => $movementQuery->count(),
                    'total_in_quantity' => (clone $movementQuery)->where('quantity', '>', 0)->sum('quantity'),
                    'total_out_quantity' => abs((clone $movementQuery)->where('quantity', '<', 0)->sum('quantity')),
                    'unique_products_moved' => (clone $movementQuery)->distinct('product_id')->count('product_id'),
                ];
            }

            // Category breakdown
            $categoryStats = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw('
                    categories.name as category_name,
                    COUNT(products.id) as product_count,
                    COALESCE(SUM(inventories.stock_level), 0) as total_stock,
                    COALESCE(SUM(inventories.stock_level * inventories.cost_price), 0) as total_value
                ')
                ->leftJoin('inventories', 'products.id', '=', 'inventories.product_id');

            if ($request->filled('warehouse_id')) {
                $categoryStats->where('inventories.warehouse_id', $request->get('warehouse_id'));
            }

            $stats['category_breakdown'] = $categoryStats
                ->groupBy('categories.id', 'categories.name')
                ->orderBy('total_value', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            return $this->successResponse(
                $stats,
                'Product statistics retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve statistics: '.$e->getMessage());
        }
    }

    /**
     * Calculate urgency level for low stock products.
     */
    private function calculateUrgencyLevel(Product $product, int $totalAvailable): string
    {
        $threshold = $product->reorder_point ?: $product->min_stock_level ?: 0;

        if ($threshold <= 0) {
            return 'normal';
        }

        $percentage = ($totalAvailable / $threshold) * 100;

        if ($percentage <= 25) {
            return 'critical';
        } elseif ($percentage <= 50) {
            return 'high';
        } elseif ($percentage <= 75) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
