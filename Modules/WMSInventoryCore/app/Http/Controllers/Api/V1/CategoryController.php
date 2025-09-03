<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\BaseApiController;
use Modules\WMSInventoryCore\app\Http\Requests\StoreCategoryRequest;
use Modules\WMSInventoryCore\app\Http\Requests\UpdateCategoryRequest;
use Modules\WMSInventoryCore\Models\Category;

class CategoryController extends BaseApiController
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::with(['parent']);

            // Filters
            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Get hierarchical structure if requested
            if ($request->boolean('hierarchical')) {
                $categories = $query->whereNull('parent_id')
                    ->with('children')
                    ->orderBy('name')
                    ->get();

                return $this->successResponse($categories);
            } else {
                // Sorting
                $sortBy = $request->get('sort_by', 'name');
                $sortOrder = $request->get('sort_order', 'asc');
                $query->orderBy($sortBy, $sortOrder);

                // Pagination
                $perPage = $request->get('per_page', 15);
                $categories = $query->paginate($perPage);

                return $this->paginatedResponse($categories, 'Categories retrieved successfully');
            }
        } catch (\Exception $e) {
            Log::error('Error fetching categories', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch categories', 500);
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate code if not provided
            if (! $request->has('code')) {
                $code = $this->generateCategoryCode($request->parent_id);
            } else {
                $code = $request->code;
            }

            $category = Category::create([
                'name' => $request->name,
                'code' => $code,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'status' => $request->status ?? 'active',
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);

            // Parent categories don't need special handling since we don't have has_children column

            $category->load('parent');

            DB::commit();

            return $this->successResponse($category, 'Category created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating category', ['error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified category.
     */
    public function show($id): JsonResponse
    {
        try {
            $category = Category::with(['parent', 'children', 'products'])
                ->find($id);

            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            // Add breadcrumb path
            $category->breadcrumb = $this->getCategoryBreadcrumb($category);

            // Add product count
            $category->product_count = $category->products()->count();
            $category->active_product_count = $category->products()->where('status', 'active')->count();

            return $this->successResponse($category);
        } catch (\Exception $e) {
            Log::error('Error fetching category', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch category', 500);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, $id): JsonResponse
    {
        try {
            $category = Category::find($id);
            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            // Prevent setting parent as self or descendant
            if ($request->has('parent_id') && $request->parent_id) {
                if ($request->parent_id == $id) {
                    return $this->errorResponse('Category cannot be its own parent', 400);
                }

                if ($this->isDescendant($category, $request->parent_id)) {
                    return $this->errorResponse('Cannot set a descendant as parent', 400);
                }
            }

            DB::beginTransaction();

            $updateData = $request->only([
                'name',
                'code',
                'description',
                'parent_id',
                'status',
            ]);

            $updateData['updated_by_id'] = auth()->id();

            $category->update($updateData);

            $category->load('parent', 'children');

            DB::commit();

            return $this->successResponse($category, 'Category updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating category', ['error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $category = Category::find($id);
            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            // Check if category has products
            if ($category->products()->count() > 0) {
                return $this->errorResponse('Cannot delete category with products', 400);
            }

            // Check if category has children
            if ($category->children()->count() > 0) {
                return $this->errorResponse('Cannot delete category with subcategories', 400);
            }

            // Delete the category
            $category->delete();

            return $this->successResponse(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting category', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to delete category', 500);
        }
    }

    /**
     * Get category tree.
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $query = Category::whereNull('parent_id')
                ->with('children');

            if (! $request->boolean('include_inactive')) {
                $query->where('status', 'active');
            }

            $tree = $query->orderBy('name')
                ->get();

            return $this->successResponse($tree);
        } catch (\Exception $e) {
            Log::error('Error fetching category tree', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch category tree', 500);
        }
    }

    /**
     * Get category products.
     */
    public function products($id, Request $request): JsonResponse
    {
        try {
            $category = Category::find($id);
            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            $query = $category->products()->with(['unit', 'brand']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return $this->successResponse($products);
        } catch (\Exception $e) {
            Log::error('Error fetching category products', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch products', 500);
        }
    }

    /**
     * Move category to different parent.
     */
    public function move(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'parent_id' => 'nullable|exists:categories,id',
            ]);

            $category = Category::find($id);
            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            // Prevent moving to self or descendant
            if ($request->parent_id) {
                if ($request->parent_id == $id) {
                    return $this->errorResponse('Category cannot be its own parent', 400);
                }

                if ($this->isDescendant($category, $request->parent_id)) {
                    return $this->errorResponse('Cannot move to a descendant category', 400);
                }
            }

            $category->update([
                'parent_id' => $request->parent_id,
                'updated_by_id' => auth()->id(),
            ]);

            $category->load('parent', 'children');

            return $this->successResponse($category, 'Category moved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error moving category', ['error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Reorder categories - not supported without display_order column.
     */
    public function reorder(Request $request): JsonResponse
    {
        return $this->errorResponse('Category reordering is not currently supported', 501);
    }

    /**
     * Search categories.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = Category::query();

            if ($request->has('q')) {
                $search = $request->q;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if (! $request->boolean('include_inactive')) {
                $query->where('status', 'active');
            }

            $categories = $query->limit(10)->get();

            return $this->successResponse($categories);
        } catch (\Exception $e) {
            Log::error('Error searching categories', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to search categories', 500);
        }
    }

    /**
     * Get category statistics.
     */
    public function statistics($id): JsonResponse
    {
        try {
            $category = Category::find($id);
            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            $stats = [
                'total_products' => $category->products()->count(),
                'active_products' => $category->products()->where('status', 'active')->count(),
                'inactive_products' => $category->products()->where('status', '!=', 'active')->count(),
                'subcategories' => $category->children()->count(),
                'total_inventory_value' => $this->calculateCategoryInventoryValue($category),
                'low_stock_products' => $this->getLowStockProductsCount($category),
                'out_of_stock_products' => $this->getOutOfStockProductsCount($category),
            ];

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching category statistics', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to fetch statistics', 500);
        }
    }

    /**
     * Generate category code.
     */
    private function generateCategoryCode($parentId = null): string
    {
        $prefix = 'CAT';

        if ($parentId) {
            $parent = Category::find($parentId);
            if ($parent) {
                $prefix = $parent->code;
            }
        }

        $lastCategory = Category::where('code', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCategory) {
            $lastNumber = intval(substr($lastCategory->code, -3));
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$prefix}-{$newNumber}";
    }

    /**
     * Check if a category is descendant of another.
     */
    private function isDescendant($category, $potentialParentId): bool
    {
        $descendants = $this->getAllDescendants($category);

        return in_array($potentialParentId, $descendants);
    }

    /**
     * Get all descendant IDs of a category.
     */
    private function getAllDescendants($category): array
    {
        $descendants = [];
        $children = Category::where('parent_id', $category->id)->get();

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->getAllDescendants($child));
        }

        return $descendants;
    }

    /**
     * Get category breadcrumb.
     */
    private function getCategoryBreadcrumb($category): array
    {
        $breadcrumb = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'code' => $current->code,
            ]);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    /**
     * Calculate category inventory value.
     */
    private function calculateCategoryInventoryValue($category): float
    {
        return $category->products()
            ->join('inventories', 'products.id', '=', 'inventories.product_id')
            ->selectRaw('SUM(inventories.quantity * COALESCE(products.unit_cost, 0)) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Get low stock products count.
     */
    private function getLowStockProductsCount($category): int
    {
        return $category->products()
            ->join('inventories', 'products.id', '=', 'inventories.product_id')
            ->whereRaw('inventories.quantity <= inventories.reorder_point')
            ->where('inventories.reorder_point', '>', 0)
            ->count();
    }

    /**
     * Get out of stock products count.
     */
    private function getOutOfStockProductsCount($category): int
    {
        return $category->products()
            ->join('inventories', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.quantity', '<=', 0)
            ->count();
    }
}
