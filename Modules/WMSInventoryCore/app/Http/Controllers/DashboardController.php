<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;
use Modules\WMSInventoryCore\Models\Adjustment;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Transfer;
use Modules\WMSInventoryCore\Models\Warehouse;

class DashboardController extends Controller
{
    /**
     * Display the inventory dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $this->authorize('wmsinventory.view-dashboard');
        // Get statistics for the dashboard
        $stats = $this->getInventoryStatistics();

        // Get recent inventory transactions
        $recentTransactions = InventoryTransaction::with(['product', 'warehouse', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get low stock products
        $lowStockProducts = $this->getLowStockProducts();

        // Get inventory value by warehouse
        $warehouseValues = $this->getInventoryValueByWarehouse();

        // Get monthly inventory transaction counts
        $monthlyTransactions = $this->getMonthlyTransactionCounts();

        return view('wmsinventorycore::dashboard.index', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'lowStockProducts' => $lowStockProducts,
            'warehouseValues' => $warehouseValues,
            'monthlyTransactions' => $monthlyTransactions,
        ]);
    }

    /**
     * Get inventory statistics for the dashboard.
     *
     * @return array
     */
    private function getInventoryStatistics()
    {
        $totalProducts = Product::count();
        $totalWarehouses = Warehouse::count();

        $totalStockValue = Inventory::join('products', 'inventories.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(inventories.stock_level * products.cost_price) as total_value'))
            ->first()->total_value ?? 0;

        $totalStockItems = Inventory::sum('stock_level');

        $pendingAdjustments = Adjustment::where('status', 'draft')->count();
        $pendingTransfers = Transfer::where('status', 'draft')->count();

        return [
            'total_products' => $totalProducts,
            'total_warehouses' => $totalWarehouses,
            'total_stock_value' => $totalStockValue,
            'total_stock_items' => $totalStockItems,
            'pending_adjustments' => $pendingAdjustments,
            'pending_transfers' => $pendingTransfers,
        ];
    }

    /**
     * Get low stock products.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getLowStockProducts()
    {
        return Product::with(['category', 'inventories.warehouse'])
            ->whereHas('inventories', function ($query) {
                $query->whereRaw('inventories.stock_level <= products.min_stock_level')
                    ->where('products.min_stock_level', '>', 0);
            })
            ->limit(5)
            ->get();
    }

    /**
     * Get inventory value by warehouse.
     *
     * @return array
     */
    private function getInventoryValueByWarehouse()
    {
        $warehouseValues = Warehouse::leftJoin('inventories', 'warehouses.id', '=', 'inventories.warehouse_id')
            ->leftJoin('products', 'inventories.product_id', '=', 'products.id')
            ->select(
                'warehouses.id',
                'warehouses.name',
                DB::raw('COALESCE(SUM(inventories.stock_level * products.cost_price), 0) as total_value')
            )
            ->groupBy('warehouses.id', 'warehouses.name')
            ->get();

        // Format for ApexCharts
        $labels = [];
        $series = [];

        foreach ($warehouseValues as $warehouse) {
            $labels[] = $warehouse->name;
            $series[] = round($warehouse->total_value, 2);
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * Get monthly inventory transaction counts.
     *
     * @return array
     */
    private function getMonthlyTransactionCounts()
    {
        $months = [];
        $adjustmentData = [];
        $transferData = [];

        // Get the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');

            // Get adjustments count for this month
            $adjustmentCount = InventoryTransaction::where('transaction_type', 'like', 'adjustment%')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            // Get transfers count for this month
            $transferCount = InventoryTransaction::where('transaction_type', 'like', 'transfer%')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $adjustmentData[] = $adjustmentCount;
            $transferData[] = $transferCount;
        }

        return [
            'months' => $months,
            'adjustment_data' => $adjustmentData,
            'transfer_data' => $transferData,
        ];
    }
}
