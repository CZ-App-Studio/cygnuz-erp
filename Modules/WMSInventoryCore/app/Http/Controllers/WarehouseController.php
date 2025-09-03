<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WMSInventoryCore\Models\Warehouse;
use Modules\WMSInventoryCore\Models\WarehouseZone;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the warehouses.
     *
     * @return Renderable
     */
    public function index()
    {
        $this->authorize('wmsinventory.view-warehouses');
        $warehouses = Warehouse::with('zones')->get();

        return view('wmsinventorycore::warehouses.index', [
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Show the form for creating a new warehouse.
     *
     * @return Renderable
     */
    public function create()
    {
        $this->authorize('wmsinventory.create-warehouse');

        return view('wmsinventorycore::warehouses.create');
    }

    /**
     * Process ajax request for warehouses datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        $this->authorize('wmsinventory.view-warehouses');
        $draw = $request->get('draw');
        $start = $request->get('start');
        $rowPerPage = $request->get('length');

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $search = $request->get('search');
        $searchValue = '';
        if (is_array($search) && isset($search['value'])) {
            $searchValue = $search['value'];
        }

        // Query builder with filters
        $query = Warehouse::query();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', '%'.$searchValue.'%')
                    ->orWhere('code', 'like', '%'.$searchValue.'%')
                    ->orWhere('address', 'like', '%'.$searchValue.'%');
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
        $warehouses = $query->skip($start)
            ->take($rowPerPage)
            ->get();

        // Format data for DataTables
        $data = [];

        foreach ($warehouses as $warehouse) {
            $data[] = [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'address' => $warehouse->address ?? '-',
                'contact_name' => $warehouse->contact_name ?? '-',
                'contact_email' => $warehouse->contact_email ?? '-',
                'contact_phone' => $warehouse->contact_phone ?? '-',
                'is_active' => $warehouse->is_active,
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
     * Store a newly created warehouse in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('wmsinventory.create-warehouse');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses',
            'address' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'zones' => 'nullable|array',
            'zones.*.name' => 'required|string|max:255',
            'zones.*.code' => 'required|string|max:50',
            'zones.*.description' => 'nullable|string|max:500',
        ]);

        try {
            $warehouse = null;
            DB::transaction(function () use ($request, $validated, &$warehouse) {
                $warehouse = Warehouse::create([
                    'name' => $validated['name'],
                    'code' => $validated['code'],
                    'address' => $validated['address'] ?? null,
                    'phone_number' => $validated['contact_phone'] ?? null,
                    'email' => $validated['contact_email'] ?? null,
                    'contact_name' => $validated['contact_name'] ?? null,
                    'contact_email' => $validated['contact_email'] ?? null,
                    'contact_phone' => $validated['contact_phone'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);

                // Create warehouse zones if provided
                if ($request->has('zones')) {
                    foreach ($request->zones as $zoneData) {
                        WarehouseZone::create([
                            'warehouse_id' => $warehouse->id,
                            'name' => $zoneData['name'],
                            'code' => $zoneData['code'],
                            'description' => $zoneData['description'] ?? null,
                            'created_by_id' => auth()->id(),
                            'updated_by_id' => auth()->id(),
                        ]);
                    }
                }
            });

            return Success::response(__('Warehouse has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create warehouse: '.$e->getMessage());

            return Error::response(__('Failed to create warehouse'));
        }
    }

    /**
     * Display the specified warehouse.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->authorize('wmsinventory.view-warehouse-inventory');
        $warehouse = Warehouse::with(['zones', 'inventories.product'])->findOrFail($id);

        // Calculate summary statistics
        $totalProducts = $warehouse->inventories()->distinct('product_id')->count();
        $totalStockValue = $warehouse->inventories()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->selectRaw('SUM(inventories.stock_level * products.cost_price) as total_value')
            ->value('total_value') ?? 0;

        $lowStockCount = $warehouse->inventories()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->whereRaw('inventories.stock_level <= products.min_stock_level')
            ->count();

        return view('wmsinventorycore::warehouses.show', [
            'warehouse' => $warehouse,
            'totalProducts' => $totalProducts,
            'totalStockValue' => \App\Helpers\FormattingHelper::formatCurrency($totalStockValue),
            'lowStockCount' => $lowStockCount,
        ]);
    }

    /**
     * Show the form for editing the specified warehouse.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->authorize('wmsinventory.edit-warehouse');
        $warehouse = Warehouse::with('zones')->findOrFail($id);

        return view('wmsinventorycore::warehouses.edit', [
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Update the specified warehouse in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('wmsinventory.edit-warehouse');
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code,'.$warehouse->id,
            'address' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'zones' => 'nullable|array',
            'zones.*.name' => 'required|string|max:255',
            'zones.*.code' => 'required|string|max:50',
            'zones.*.description' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, $validated, $warehouse) {
                $warehouse->update([
                    'name' => $validated['name'],
                    'code' => $validated['code'],
                    'address' => $validated['address'] ?? null,
                    'phone_number' => $validated['contact_phone'] ?? null,
                    'email' => $validated['contact_email'] ?? null,
                    'contact_name' => $validated['contact_name'] ?? null,
                    'contact_email' => $validated['contact_email'] ?? null,
                    'contact_phone' => $validated['contact_phone'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                    'updated_by_id' => auth()->id(),
                ]);

                // Update warehouse zones if provided
                if ($request->has('zones')) {
                    // Delete existing zones first
                    $warehouse->zones()->delete();

                    // Create new zones
                    foreach ($request->zones as $zoneData) {
                        WarehouseZone::create([
                            'warehouse_id' => $warehouse->id,
                            'name' => $zoneData['name'],
                            'code' => $zoneData['code'],
                            'description' => $zoneData['description'] ?? null,
                            'created_by_id' => auth()->id(),
                            'updated_by_id' => auth()->id(),
                        ]);
                    }
                }
            });

            return Success::response(__('Warehouse has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update warehouse: '.$e->getMessage());

            return Error::response(__('Failed to update warehouse'));
        }
    }

    /**
     * Remove the specified warehouse from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('wmsinventory.delete-warehouse');
        try {
            $warehouse = Warehouse::findOrFail($id);

            // Check if warehouse has inventory
            if ($warehouse->inventories()->count() > 0) {
                return Error::response(__('Cannot delete warehouse with existing inventory'));
            }

            DB::transaction(function () use ($warehouse) {
                // Delete warehouse zones
                $warehouse->zones()->delete();

                // Delete the warehouse
                $warehouse->delete();
            });

            return Success::response(__('Warehouse has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete warehouse: '.$e->getMessage());

            return Error::response(__('Failed to delete warehouse'));
        }
    }

    /**
     * Store a new zone for a warehouse.
     *
     * @param  int  $warehouseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeZone(Request $request, $warehouseId)
    {
        $this->authorize('wmsinventory.manage-warehouse-zones');
        $warehouse = Warehouse::findOrFail($warehouseId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouse_zones,code,NULL,id,warehouse_id,'.$warehouseId,
        ]);

        try {
            WarehouseZone::create([
                'warehouse_id' => $warehouse->id,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);

            return Success::response(__('Zone has been added successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to add zone: '.$e->getMessage());

            return Error::response(__('Failed to add zone'));
        }
    }

    /**
     * Update a warehouse zone.
     *
     * @param  int  $warehouseId
     * @param  int  $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateZone(Request $request, $warehouseId, $zoneId)
    {
        $this->authorize('wmsinventory.manage-warehouse-zones');
        $zone = WarehouseZone::where('warehouse_id', $warehouseId)->findOrFail($zoneId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouse_zones,code,'.$zone->id.',id,warehouse_id,'.$warehouseId,
        ]);

        try {
            $zone->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'updated_by_id' => auth()->id(),
            ]);

            return Success::response(__('Zone has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update zone: '.$e->getMessage());

            return Error::response(__('Failed to update zone'));
        }
    }

    /**
     * Remove a warehouse zone.
     *
     * @param  int  $warehouseId
     * @param  int  $zoneId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyZone($warehouseId, $zoneId)
    {
        $this->authorize('wmsinventory.manage-warehouse-zones');
        try {
            $zone = WarehouseZone::where('warehouse_id', $warehouseId)->findOrFail($zoneId);

            // Check if zone has bin locations
            if ($zone->binLocations()->count() > 0) {
                return Error::response(__('Cannot delete zone with existing bin locations'));
            }

            $zone->delete();

            return Success::response(__('Zone has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete zone: '.$e->getMessage());

            return Error::response(__('Failed to delete zone'));
        }
    }

    /**
     * Process ajax request for warehouse inventory datatable.
     *
     * @param  int  $warehouseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventoryDataAjax(Request $request, $warehouseId)
    {
        $this->authorize('wmsinventory.view-warehouse-inventory');
        $draw = $request->get('draw');
        $start = $request->get('start');
        $rowPerPage = $request->get('length');

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $search = $request->get('search');
        $searchValue = '';
        if (is_array($search) && isset($search['value'])) {
            $searchValue = $search['value'];
        }

        // Get the warehouse
        $warehouse = Warehouse::findOrFail($warehouseId);

        // Query builder with joins to get inventory with product details
        $query = $warehouse->inventories()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select([
                'inventories.id',
                'products.code as product_code',
                'products.name as product_name',
                'categories.name as category',
                'inventories.stock_level',
                'products.min_stock_level',
                'products.max_stock_level',
            ]);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('products.name', 'like', '%'.$searchValue.'%')
                    ->orWhere('products.code', 'like', '%'.$searchValue.'%')
                    ->orWhere('categories.name', 'like', '%'.$searchValue.'%');
            });
        }

        // Get total count
        $totalRecords = $query->count();

        // Handle ordering
        if (! empty($columnIndex_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'];
            $columnName = $columnName_arr[$columnIndex]['data'];
            $columnSortOrder = $order_arr[0]['dir'];

            // Map the column names to the actual database fields
            $columnMappings = [
                'product_code' => 'products.code',
                'product_name' => 'products.name',
                'category' => 'categories.name',
                'stock_level' => 'inventories.stock_level',
                'min_stock_level' => 'products.min_stock_level',
                'max_stock_level' => 'products.max_stock_level',
            ];

            if (isset($columnMappings[$columnName])) {
                $query->orderBy($columnMappings[$columnName], $columnSortOrder);
            } else {
                $query->orderBy('products.name', 'asc');
            }
        } else {
            $query->orderBy('products.name', 'asc');
        }

        // Apply pagination
        $inventory = $query->skip($start)
            ->take($rowPerPage)
            ->get();

        // Format data for DataTables
        $data = [];

        foreach ($inventory as $item) {
            $data[] = [
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'category' => $item->category,
                'stock_level' => $item->stock_level,
                'min_stock_level' => $item->min_stock_level,
                'max_stock_level' => $item->max_stock_level,
                'status' => '', // Will be determined client-side
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
     * Search warehouses for AJAX dropdown (Global endpoint for modules)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchWarehouses(Request $request)
    {
        $this->authorize('wmsinventory.view-warehouses');
        $search = $request->get('search', '');
        $limit = $request->get('limit', 20);

        $query = Warehouse::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $warehouses = $query->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json($warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'text' => $warehouse->name.' ('.$warehouse->code.')', // For Select2 display
            ];
        }));
    }
}
