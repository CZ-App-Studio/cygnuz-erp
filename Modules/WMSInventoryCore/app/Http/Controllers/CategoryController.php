<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\Models\Category;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @return Renderable
     */
    public function index()
    {
        $this->authorize('wmsinventory.view-categories');
        $categories = Category::withCount('products')->get();

        return view('wmsinventorycore::categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Process ajax request for categories datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        $this->authorize('wmsinventory.view-categories');
        $categories = Category::with('parent')->withCount('products');

        return DataTables::of($categories)
            ->addColumn('parent_name', function ($category) {
                return $category->parent ? $category->parent->name : '-';
            })
            ->addColumn('actions', function ($category) {
                $actions = [];

                // Edit action
                if (auth()->user()->can('wmsinventory.edit-category')) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editCategory({$category->id}, '".addslashes($category->name)."', '".addslashes($category->description ?? '')."', ".($category->parent_id ?? 'null').", '{$category->status}')",
                    ];
                }

                // Delete action
                if (auth()->user()->can('wmsinventory.delete-category')) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'class' => 'text-danger',
                        'onclick' => "deleteCategory({$category->id})",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $category->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Store a newly created category in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('wmsinventory.create-category');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            // Generate unique category code
            $code = strtoupper(substr($validated['name'], 0, 3)).'-'.str_pad(Category::count() + 1, 4, '0', STR_PAD_LEFT);

            // Ensure code is unique
            while (Category::where('code', $code)->exists()) {
                $code = strtoupper(substr($validated['name'], 0, 3)).'-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            }

            Category::create([
                'name' => $validated['name'],
                'code' => $code,
                'description' => $validated['description'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'status' => $validated['status'],
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);

            return Success::response(__('Category has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create category: '.$e->getMessage());

            return Error::response(__('Failed to create category'));
        }
    }

    /**
     * Update the specified category in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('wmsinventory.edit-category');
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:active,inactive',
        ]);

        // Prevent circular reference
        if ($validated['parent_id'] == $category->id) {
            return Error::response(__('A category cannot be its own parent'));
        }

        try {
            $category->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'status' => $validated['status'],
                'updated_by_id' => auth()->id(),
            ]);

            return Success::response(__('Category has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update category: '.$e->getMessage());

            return Error::response(__('Failed to update category'));
        }
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('wmsinventory.delete-category');
        try {
            $category = Category::findOrFail($id);

            // Check if category has products
            if ($category->products()->count() > 0) {
                return Error::response(__('Cannot delete category with existing products'));
            }

            // Check if category has child categories
            if ($category->children()->count() > 0) {
                return Error::response(__('Cannot delete category with child categories'));
            }

            $category->delete();

            return Success::response(__('Category has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete category: '.$e->getMessage());

            return Error::response(__('Failed to delete category'));
        }
    }
}
