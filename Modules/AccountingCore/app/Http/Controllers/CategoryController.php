<?php

namespace Modules\AccountingCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Services\AddonService\AddonService;
use App\Services\Settings\ModuleSettingsService;
use Illuminate\Http\Request;
use Modules\AccountingCore\app\Models\BasicTransactionCategory;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:accountingcore.categories.index')->only(['index', 'indexAjax']);
        $this->middleware('permission:accountingcore.categories.store')->only(['store']);
        $this->middleware('permission:accountingcore.categories.show')->only(['show']);
        $this->middleware('permission:accountingcore.categories.update')->only(['update']);
        $this->middleware('permission:accountingcore.categories.destroy')->only(['destroy']);
        $this->middleware('permission:accountingcore.categories.search')->only(['search']);
    }

    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        // Check if AccountingPro is enabled
        $addonService = app(AddonService::class);
        if ($addonService->isAddonEnabled('AccountingPro')) {
            return redirect()->route('accountingpro.settings.chart-of-accounts.index');
        }

        // Get module settings
        $settingsService = app(ModuleSettingsService::class);
        $allowCustomCategories = $settingsService->get('AccountingCore', 'allow_custom_categories', true);
        $hierarchyLevels = $settingsService->get('AccountingCore', 'category_hierarchy_levels', 2);

        // Get parent categories for the form
        $parentCategories = BasicTransactionCategory::active()
            ->parents()
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // Breadcrumb data
        $breadcrumbs = [
            ['name' => __('Accounting'), 'url' => route('accountingcore.dashboard')],
            ['name' => __('Categories'), 'url' => ''],
        ];

        return view('accountingcore::categories.index', compact('parentCategories', 'breadcrumbs', 'allowCustomCategories', 'hierarchyLevels'));
    }

    /**
     * Get categories data for DataTables.
     */
    public function indexAjax(Request $request)
    {
        $query = BasicTransactionCategory::with(['parent', 'createdBy']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return DataTables::of($query)
            ->addColumn('type_badge', function ($model) {
                return $model->badge_html;
            })
            ->addColumn('icon_display', function ($model) {
                return $model->icon ? "<i class='{$model->icon}'></i>" : '';
            })
            ->addColumn('color_display', function ($model) {
                if ($model->color) {
                    return '<span class="badge" style="background-color: '.$model->color.'">'.$model->color.'</span>';
                }

                return '';
            })
            ->addColumn('parent_name', function ($model) {
                return $model->parent ? $model->parent->name : '-';
            })
            ->addColumn('transaction_count', function ($model) {
                return $model->transactions()->count();
            })
            ->addColumn('status', function ($model) {
                return $model->is_active
                    ? '<span class="badge bg-label-success">'.__('Active').'</span>'
                    : '<span class="badge bg-label-secondary">'.__('Inactive').'</span>';
            })
            ->addColumn('user', function ($model) {
                return view('components.datatable-user', ['user' => $model->createdBy])->render();
            })
            ->addColumn('actions', function ($model) {
                $actions = [];

                if (auth()->user()->can('accountingcore.categories.update')) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editCategory({$model->id})",
                    ];
                }

                // Only allow delete if no transactions and user has permission
                if (auth()->user()->can('accountingcore.categories.destroy') && $model->transactions()->count() == 0) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteCategory({$model->id})",
                        'class' => 'text-danger',
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $model->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['type_badge', 'icon_display', 'color_display', 'status', 'user', 'actions'])
            ->make(true);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        // Get module settings
        $settingsService = app(ModuleSettingsService::class);
        $allowCustomCategories = $settingsService->get('AccountingCore', 'allow_custom_categories', true);
        $hierarchyLevels = $settingsService->get('AccountingCore', 'category_hierarchy_levels', 2);

        // Check if custom categories are allowed
        if (! $allowCustomCategories) {
            return Error::response(__('Custom categories are not allowed. Please contact administrator.'));
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense',
            'parent_id' => 'nullable|exists:basic_transaction_categories,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        try {
            $data = $request->only(['name', 'type', 'parent_id', 'icon', 'color']);
            $data['is_active'] = $request->get('is_active', true);
            $data['created_by_id'] = auth()->id();

            // Validate parent category type matches
            if ($request->filled('parent_id')) {
                $parent = BasicTransactionCategory::find($request->parent_id);
                if ($parent && $parent->type !== $request->type) {
                    return Error::response(__('Parent category type must match the category type'));
                }

                // Check hierarchy level
                $level = 1;
                $currentParent = $parent;
                while ($currentParent->parent_id) {
                    $level++;
                    $currentParent = $currentParent->parent;
                }

                if ($level >= $hierarchyLevels) {
                    return Error::response(__('Maximum category hierarchy level reached. Maximum allowed: ').$hierarchyLevels);
                }
            }

            $category = BasicTransactionCategory::create($data);

            return Success::response([
                'message' => __('Category created successfully'),
                'category' => $category,
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to create category: ').$e->getMessage());
        }
    }

    /**
     * Show the specified category.
     */
    public function show($id)
    {
        $category = BasicTransactionCategory::with(['parent', 'children', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        return Success::response([
            'category' => $category,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense',
            'parent_id' => 'nullable|exists:basic_transaction_categories,id|not_in:'.$id,
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        try {
            $category = BasicTransactionCategory::findOrFail($id);

            // Check if changing type would affect transactions
            if ($category->type !== $request->type && $category->transactions()->exists()) {
                return Error::response(__('Cannot change category type when it has transactions'));
            }

            // Validate parent category type matches
            if ($request->filled('parent_id')) {
                $parent = BasicTransactionCategory::find($request->parent_id);
                if ($parent && $parent->type !== $request->type) {
                    return Error::response(__('Parent category type must match the category type'));
                }

                // Prevent circular reference
                if ($this->wouldCreateCircularReference($id, $request->parent_id)) {
                    return Error::response(__('This would create a circular reference'));
                }
            }

            $data = $request->only(['name', 'type', 'parent_id', 'icon', 'color']);
            $data['is_active'] = $request->get('is_active', true);
            $data['updated_by_id'] = auth()->id();

            $category->update($data);

            return Success::response([
                'message' => __('Category updated successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to update category: ').$e->getMessage());
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id)
    {
        try {
            $category = BasicTransactionCategory::findOrFail($id);

            // Check if category has transactions
            if ($category->transactions()->exists()) {
                return Error::response(__('Cannot delete category with existing transactions'));
            }

            // Check if category has children
            if ($category->hasChildren()) {
                return Error::response(__('Cannot delete category with sub-categories'));
            }

            $category->delete();

            return Success::response([
                'message' => __('Category deleted successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to delete category: ').$e->getMessage());
        }
    }

    /**
     * Get categories for dropdown.
     */
    public function search(Request $request)
    {
        $query = BasicTransactionCategory::active();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $categories = $query->orderBy('name')->limit(50)->get();

        $results = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'text' => $category->full_path,
                'icon' => $category->icon,
                'color' => $category->color,
                'type' => $category->type,
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Check if setting a parent would create a circular reference.
     */
    private function wouldCreateCircularReference($categoryId, $parentId)
    {
        if ($categoryId == $parentId) {
            return true;
        }

        $parent = BasicTransactionCategory::find($parentId);
        while ($parent) {
            if ($parent->parent_id == $categoryId) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }
}
