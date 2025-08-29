<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\Warehouse;
use App\Helpers\FormattingHelper;

class ReportController extends Controller
{
    /**
     * Display inventory valuation report
     */
    public function inventoryValuation(Request $request)
    {
        $this->authorize('wmsinventory.view-inventory-valuation');
        $warehouses = Warehouse::all();
        $selectedWarehouse = $request->input('warehouse_id');

        $query = Inventory::with(['product', 'warehouse']);

        if ($selectedWarehouse) {
            $query->where('warehouse_id', $selectedWarehouse);
        }

        $inventories = $query->get();

        $totalValue = 0;
        foreach ($inventories as $inventory) {
            if ($inventory->product) {
                $totalValue += $inventory->stock_level * $inventory->product->cost_price;
            }
        }

        return view('wmsinventorycore::reports.inventory-valuation', [
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
            'warehouseProducts' => $inventories,
            'totalValue' => FormattingHelper::formatCurrency($totalValue),
        ]);
    }

    /**
     * Display stock movement report
     */
    public function stockMovement(Request $request)
    {
        $this->authorize('wmsinventory.view-stock-movement');
        $products = Product::all();
        $warehouses = Warehouse::all();
        $selectedProduct = $request->input('product_id');
        $selectedWarehouse = $request->input('warehouse_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $movements = collect();

        if ($selectedProduct) {
            // Get all inventory transactions for the selected product
            $query = DB::table('inventory_transactions')
                ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
                ->join('warehouses', 'inventory_transactions.warehouse_id', '=', 'warehouses.id')
                ->select(
                    'inventory_transactions.*',
                    'products.name as product_name',
                    'products.sku',
                    'warehouses.name as warehouse_name'
                )
                ->where('inventory_transactions.product_id', $selectedProduct);

            if ($selectedWarehouse) {
                $query->where('inventory_transactions.warehouse_id', $selectedWarehouse);
            }

            if ($startDate) {
                $query->whereDate('inventory_transactions.created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('inventory_transactions.created_at', '<=', $endDate);
            }

            $movements = $query->orderBy('inventory_transactions.created_at', 'desc')->get();
        }

        return view('wmsinventorycore::reports.stock-movement', [
            'products' => $products,
            'warehouses' => $warehouses,
            'selectedProduct' => $selectedProduct,
            'selectedWarehouse' => $selectedWarehouse,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'movements' => $movements,
        ]);
    }

    /**
     * Display low stock report
     */
    public function lowStock(Request $request)
    {
        $this->authorize('wmsinventory.view-low-stock');
        $warehouses = Warehouse::all();
        $selectedWarehouse = $request->input('warehouse_id');
        $threshold = $request->input('threshold', 10);

        $query = Inventory::with(['product', 'warehouse'])
            ->whereHas('product', function ($q) {
                $q->whereColumn('inventories.stock_level', '<=', 'products.reorder_point');
            });

        if ($selectedWarehouse) {
            $query->where('warehouse_id', $selectedWarehouse);
        }

        $lowStockInventories = $query->get();

        return view('wmsinventorycore::reports.low-stock', [
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
            'threshold' => $threshold,
            'lowStockProducts' => $lowStockInventories,
        ]);
    }
}
