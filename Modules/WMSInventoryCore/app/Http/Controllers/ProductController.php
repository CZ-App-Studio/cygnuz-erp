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
use Modules\WMSInventoryCore\Models\Category;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\Option;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Unit;
use Modules\WMSInventoryCore\Models\Variant;
use Modules\WMSInventoryCore\Models\Warehouse;
use Modules\WMSInventoryCore\app\Services\WMSInventoryCoreSettingsService;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return Renderable
     */
    public function index()
    {
        $this->authorize('wmsinventory.view-products');

        $categories = Category::all();
        $warehouses = Warehouse::all();

        return view('wmsinventorycore::products.index', [
            'categories' => $categories,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Process ajax request for products datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        $this->authorize('wmsinventory.view-products');
        $draw = $request->get('draw');
        $start = $request->get('start');
        $rowPerPage = $request->get('length');

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $searchValue = $request->get('search')['value'] ?? '';
        $categoryFilter = $request->get('category_id') ?? '';
        $warehouseFilter = $request->get('warehouse_id') ?? '';

        // Query builder with filters
        $query = Product::with(['category', 'unit']);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', '%'.$searchValue.'%')
                    ->orWhere('sku', 'like', '%'.$searchValue.'%')
                    ->orWhere('barcode', 'like', '%'.$searchValue.'%');
            });
        }

        if (! empty($categoryFilter)) {
            $query->where('category_id', $categoryFilter);
        }

        if (! empty($warehouseFilter)) {
            $query->whereHas('inventory', function ($q) use ($warehouseFilter) {
                $q->where('warehouse_id', $warehouseFilter);
            });
        }

        // Get total count
        $totalRecords = $query->count();

        // Handle ordering
        if (! empty($columnIndex_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'];
            $columnName = $columnName_arr[$columnIndex]['data'];
            $columnSortOrder = $order_arr[0]['dir'];

            if ($columnName != 'actions') {
                $query->orderBy($columnName, $columnSortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        // Apply pagination
        $products = $query->skip($start)
            ->take($rowPerPage)
            ->get();

        // Format data for DataTables
        $data = [];

        foreach ($products as $product) {
            $stockCount = $product->inventory->sum('stock_level');

            $data[] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?? '-',
                'barcode' => $product->barcode ?? '-',
                'category_name' => $product->category ? $product->category->name : '-',
                'unit_name' => $product->unit ? $product->unit->name : '-',
                'stock' => $stockCount,
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'actions' => null, // Will be rendered client-side
            ];
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new product.
     *
     * @return Renderable
     */
    public function create()
    {
        $this->authorize('wmsinventory.create-product');
        $categories = Category::all();
        $units = Unit::all();
        $warehouses = Warehouse::all();
        $options = Option::all()->groupBy('option_group');

        return view('wmsinventorycore::products.create', [
            'categories' => $categories,
            'units' => $units,
            'warehouses' => $warehouses,
            'options' => $options,
        ]);
    }

    /**
     * Store a newly created product in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('wmsinventory.create-product');

        // Build validation rules based on settings
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products',
            'sku' => 'nullable|string|max:50|unique:products',
            'barcode' => 'nullable|string|max:50',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'alert_on' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ];

        // Check if product images are required
        if (WMSInventoryCoreSettingsService::requireProductImages()) {
            $rules['image'] = 'required|string';
        } else {
            $rules['image'] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        try {
            $product = null;
            DB::transaction(function () use ($request, $validated, &$product) {
                // Auto-generate SKU if enabled and not provided
                if (WMSInventoryCoreSettingsService::isAutoGenerateSku() && empty($validated['sku'])) {
                    $validated['sku'] = WMSInventoryCoreSettingsService::generateNextSku();
                }

                $product = Product::create([
                    'name' => $validated['name'],
                    'code' => $validated['code'],
                    'sku' => $validated['sku'] ?? null,
                    'barcode' => $validated['barcode'] ?? null,
                    'category_id' => $validated['category_id'],
                    'unit_id' => $validated['unit_id'],
                    'cost_price' => $validated['cost_price'] ?? null,
                    'selling_price' => $validated['selling_price'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'alert_on' => $validated['alert_on'] ?? null,
                    'status' => $validated['status'],
                    'track_weight' => $request->boolean('track_weight'),
                    'track_quantity' => $request->boolean('track_quantity'),
                    'image' => $validated['image'] ?? null,
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return redirect()->route('wmsinventorycore.products.show', $product->id)
                ->with('success', __('Product has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create product: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to create product'))
                ->withInput();
        }
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->authorize('wmsinventory.view-products');
        $product = Product::with(['category', 'unit', 'inventory.warehouse', 'variants.options'])
            ->findOrFail($id);

        // Calculate total stock across all warehouses
        $totalStock = $product->inventory->sum('stock_level');

        // Calculate total inventory value (stock * cost price)
        $totalValue = $totalStock * $product->cost_price;

        // Get warehouse stock information
        $warehouseStock = $product->inventory->map(function ($inventory) use ($product) {
            return (object) [
                'warehouse' => $inventory->warehouse,
                'available_quantity' => $inventory->available_quantity ?? $inventory->stock_level,
                'reserved_quantity' => $inventory->reserved_quantity ?? 0,
                'value' => FormattingHelper::formatCurrency($inventory->stock_level * $product->cost_price),
            ];
        });

        // Get recent transactions
        $transactions = DB::table('inventory_transactions as it')
            ->join('warehouses as w', 'it.warehouse_id', '=', 'w.id')
            ->where('it.product_id', $id)
            ->whereNull('it.deleted_at')
            ->select([
                'it.*',
                'w.name as warehouse_name',
            ])
            ->orderBy('it.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                $transaction->warehouse = (object) ['name' => $transaction->warehouse_name];

                return $transaction;
            });

        return view('wmsinventorycore::products.show', [
            'product' => $product,
            'totalStock' => $totalStock,
            'totalValue' => FormattingHelper::formatCurrency($totalValue),
            'warehouseStock' => $warehouseStock,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Show the form for editing the specified product.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->authorize('wmsinventory.edit-product');
        $product = Product::with(['category', 'unit', 'inventory.warehouse', 'variants.options'])
            ->findOrFail($id);

        $categories = Category::all();
        $units = Unit::all();
        $warehouses = Warehouse::all();
        $options = Option::all()->groupBy('option_group');

        return view('wmsinventorycore::products.edit', [
            'product' => $product,
            'categories' => $categories,
            'units' => $units,
            'warehouses' => $warehouses,
            'options' => $options,
        ]);
    }

    /**
     * Update the specified product in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('wmsinventory.edit-product');
        $product = Product::findOrFail($id);

        // Build validation rules based on settings
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products,code,'.$product->id,
            'sku' => 'nullable|string|max:50|unique:products,sku,'.$product->id,
            'barcode' => 'nullable|string|max:50',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'alert_on' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ];

        // Check if product images are required
        if (WMSInventoryCoreSettingsService::requireProductImages()) {
            $rules['image'] = 'required|string';
        } else {
            $rules['image'] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        try {
            DB::transaction(function () use ($product, $validated, $request) {
                $product->update([
                    'name' => $validated['name'],
                    'code' => $validated['code'],
                    'sku' => $validated['sku'] ?? null,
                    'barcode' => $validated['barcode'] ?? null,
                    'category_id' => $validated['category_id'],
                    'unit_id' => $validated['unit_id'],
                    'cost_price' => $validated['cost_price'] ?? null,
                    'selling_price' => $validated['selling_price'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'alert_on' => $validated['alert_on'] ?? null,
                    'status' => $validated['status'],
                    'track_weight' => $request->boolean('track_weight'),
                    'track_quantity' => $request->boolean('track_quantity'),
                    'image' => $validated['image'] ?? $product->image,
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return redirect()->route('wmsinventorycore.products.show', $product->id)
                ->with('success', __('Product has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update product: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to update product'))
                ->withInput();
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('wmsinventory.delete-product');
        try {
            $product = Product::findOrFail($id);

            DB::transaction(function () use ($product) {
                // Delete related inventory records
                $product->inventory()->delete();

                // Delete variant records if any
                if ($product->variants) {
                    foreach ($product->variants as $variant) {
                        $variant->options()->detach();
                    }
                    $product->variants()->delete();
                }

                // Delete the product
                $product->delete();
            });

            return Success::response(__('Product has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete product: '.$e->getMessage());

            return Error::response(__('Failed to delete product'));
        }
    }

    /**
     * Create variants for a product based on selected options.
     *
     * @return void
     */
    private function createProductVariants(Product $product, array $optionIds)
    {
        // Get all option values for the selected options
        $options = Option::whereIn('id', $optionIds)->with('values')->get();

        // Get all possible combinations of option values
        $optionValueSets = [];
        foreach ($options as $option) {
            $optionValueSets[] = $option->values->pluck('id')->toArray();
        }

        // Generate cartesian product of all option value sets
        $combinations = $this->cartesianProduct($optionValueSets);

        // Create a variant for each combination
        foreach ($combinations as $combination) {
            $variant = Variant::create([
                'product_id' => $product->id,
                'sku' => $product->sku ? $product->sku.'-'.implode('-', $combination) : null,
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);

            // Attach option values to variant
            $variant->options()->attach($combination);
        }
    }

    /**
     * Generate all possible combinations of option values.
     *
     * @return array
     */
    private function cartesianProduct(array $arrays)
    {
        $result = [[]];
        foreach ($arrays as $property => $propertyValues) {
            $tmp = [];
            foreach ($result as $resultItem) {
                foreach ($propertyValues as $propertyValue) {
                    $tmp[] = array_merge($resultItem, [$propertyValue]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }

    /**
     * Global product search for Select2 integration
     * Used by other modules (Billing, Sales, etc.)
     */
    public function searchProducts(Request $request)
    {
        // $this->authorize('wmsinventory.search-products');
        try {
            $search = $request->get('search', '');
            $warehouseId = $request->get('warehouse_id');
            $currencyId = $request->get('currency_id');
            $sellableOnly = $request->get('sellable_only', true);
            $limit = $request->get('limit', 50);

            $query = Product::query()
                ->where('status', 'active')
                ->where(function ($q) use ($search) {
                    if (! empty($search)) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    }
                })
                ->with(['unit', 'category']);

            if ($sellableOnly) {
                $query->where('is_sellable', true);
            }

            if ($warehouseId) {
                $query->whereHas('inventories', function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                });
            }

            $products = $query->limit($limit)->get()->map(function ($product) use ($warehouseId, $currencyId) {
                $availableStock = 0;
                $price = $product->selling_price ?? 0;

                // Get stock level for specific warehouse if provided
                if ($warehouseId) {
                    $inventory = $product->inventories()
                        ->where('warehouse_id', $warehouseId)
                        ->first();
                    $availableStock = $inventory ? $inventory->stock_level : 0;
                } else {
                    // Get total stock across all warehouses
                    $availableStock = $product->inventories()->sum('stock_level');
                }

                // Apply currency conversion if MultiCurrency module is available
                if ($currencyId && class_exists('\Modules\MultiCurrency\app\Services\CurrencyService')) {
                    try {
                        $currencyService = app(\Modules\MultiCurrency\app\Services\CurrencyService::class);
                        $price = $currencyService->convertAmount($price, 'USD', $currencyId); // Assuming base currency is USD
                    } catch (\Exception $e) {
                        Log::warning('Currency conversion failed: '.$e->getMessage());
                    }
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'code' => $product->code,
                    'description' => $product->description,
                    'unit_name' => $product->unit ? $product->unit->name : '',
                    'unit_id' => $product->unit_id,
                    'category_name' => $product->category ? $product->category->name : '',
                    'selling_price' => $price,
                    'cost_price' => $product->cost_price ?? 0,
                    'available_stock' => $availableStock,
                    'track_quantity' => $product->track_quantity ?? false,
                    'is_sellable' => $product->is_sellable ?? false,
                    'min_stock_level' => $product->min_stock_level ?? 0,
                    'reorder_point' => $product->reorder_point ?? 0,
                ];
            });

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Global product search failed: '.$e->getMessage());

            return response()->json([]);
        }
    }
}
