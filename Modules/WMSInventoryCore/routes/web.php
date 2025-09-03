<?php

use Illuminate\Support\Facades\Route;
use Modules\WMSInventoryCore\app\Http\Controllers\AdjustmentController;
use Modules\WMSInventoryCore\app\Http\Controllers\AdjustmentTypeController;
use Modules\WMSInventoryCore\app\Http\Controllers\CategoryController;
use Modules\WMSInventoryCore\app\Http\Controllers\DashboardController;
use Modules\WMSInventoryCore\app\Http\Controllers\ProductController;
use Modules\WMSInventoryCore\app\Http\Controllers\PurchaseController;
use Modules\WMSInventoryCore\app\Http\Controllers\ReportController;
use Modules\WMSInventoryCore\app\Http\Controllers\SaleController;
use Modules\WMSInventoryCore\app\Http\Controllers\TransferController;
use Modules\WMSInventoryCore\app\Http\Controllers\UnitController;
use Modules\WMSInventoryCore\app\Http\Controllers\VendorController;
use Modules\WMSInventoryCore\app\Http\Controllers\WarehouseController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->prefix('inventory')->name('wmsinventorycore.')->group(function () {
    // Test route to verify file is loading
    Route::get('/test-route-loading', function () {
        return 'Routes are loading';
    })->name('test.loading');
    // Dashboard - requires view permission
    Route::middleware(['permission:wmsinventory.view-dashboard'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    });

    // Product search - accessible without specific permission for purchase order creation
    Route::get('/products/search', [ProductController::class, 'searchProducts'])->name('products.search');

    // Products - grouped by permission type
    Route::middleware(['permission:wmsinventory.create-product'])->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    });

    Route::middleware(['permission:wmsinventory.view-products'])->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/data', [ProductController::class, 'getDataAjax'])->name('products.data');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    });

    Route::middleware(['permission:wmsinventory.edit-product'])->group(function () {
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}', [ProductController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.delete-product'])->group(function () {
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Categories - grouped by permission type
    Route::middleware(['permission:wmsinventory.view-categories'])->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/data', [CategoryController::class, 'getDataAjax'])->name('categories.data');
        Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    });

    Route::middleware(['permission:wmsinventory.create-category'])->group(function () {
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    });

    Route::middleware(['permission:wmsinventory.edit-category'])->group(function () {
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.delete-category'])->group(function () {
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Units - grouped by permission type
    Route::middleware(['permission:wmsinventory.view-units'])->group(function () {
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::get('/units/data', [UnitController::class, 'getDataAjax'])->name('units.data');
        Route::get('/units/{unit}', [UnitController::class, 'show'])->name('units.show');
    });

    Route::middleware(['permission:wmsinventory.create-unit'])->group(function () {
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    });

    Route::middleware(['permission:wmsinventory.edit-unit'])->group(function () {
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::patch('/units/{unit}', [UnitController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.delete-unit'])->group(function () {
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
    });

    // Warehouses - grouped by permission type
    // Create routes must come before parameterized routes to avoid 404 errors
    Route::middleware(['permission:wmsinventory.create-warehouse'])->group(function () {
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    });

    Route::middleware(['permission:wmsinventory.view-warehouses'])->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/data', [WarehouseController::class, 'getDataAjax'])->name('warehouses.data');
        Route::get('/warehouses/search', [WarehouseController::class, 'searchWarehouses'])->name('warehouses.search');
        Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');
    });

    Route::middleware(['permission:wmsinventory.view-warehouse-inventory'])->group(function () {
        Route::get('/warehouses/{warehouse}/inventory/data', [WarehouseController::class, 'getInventoryDataAjax'])->name('warehouse.inventory.data');
    });

    Route::middleware(['permission:wmsinventory.edit-warehouse'])->group(function () {
        Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::patch('/warehouses/{warehouse}', [WarehouseController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.delete-warehouse'])->group(function () {
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    });

    Route::middleware(['permission:wmsinventory.manage-warehouse-zones'])->group(function () {
        Route::post('/warehouses/{warehouse}/zones', [WarehouseController::class, 'storeZone'])->name('warehouses.zones.store');
        Route::put('/warehouses/{warehouse}/zones/{zone}', [WarehouseController::class, 'updateZone'])->name('warehouses.zones.update');
        Route::delete('/warehouses/{warehouse}/zones/{zone}', [WarehouseController::class, 'destroyZone'])->name('warehouses.zones.destroy');
    });

    // Adjustments - grouped by permission type
    // Create routes must come before parameterized routes to avoid 404 errors
    Route::middleware(['permission:wmsinventory.create-adjustment'])->group(function () {
        Route::get('/adjustments/create', [AdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('/adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
    });

    Route::middleware(['permission:wmsinventory.view-adjustments'])->group(function () {
        Route::get('/adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('/adjustments/data', [AdjustmentController::class, 'getDataAjax'])->name('adjustments.data');
        Route::get('/adjustments/warehouse-products', [AdjustmentController::class, 'getWarehouseProducts'])->name('adjustments.warehouse-products');
        Route::get('/adjustments/{adjustment}', [AdjustmentController::class, 'show'])->name('adjustments.show');
    });

    Route::middleware(['permission:wmsinventory.edit-adjustment'])->group(function () {
        Route::get('/adjustments/{adjustment}/edit', [AdjustmentController::class, 'edit'])->name('adjustments.edit');
        Route::put('/adjustments/{adjustment}', [AdjustmentController::class, 'update'])->name('adjustments.update');
        Route::patch('/adjustments/{adjustment}', [AdjustmentController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.approve-adjustment'])->group(function () {
        Route::patch('/adjustments/{adjustment}/approve', [AdjustmentController::class, 'approve'])->name('adjustments.approve');
    });

    Route::middleware(['permission:wmsinventory.delete-adjustment'])->group(function () {
        Route::delete('/adjustments/{adjustment}', [AdjustmentController::class, 'destroy'])->name('adjustments.destroy');
    });

    // Adjustment Types - grouped by permission type
    Route::middleware(['permission:wmsinventory.view-adjustment-types'])->group(function () {
        Route::get('/adjustment-types', [AdjustmentTypeController::class, 'index'])->name('adjustmenttypes.index');
        Route::get('/adjustment-types/data', [AdjustmentTypeController::class, 'getDataAjax'])->name('adjustment-types.data');
        Route::get('/adjustment-types/{adjustmentType}', [AdjustmentTypeController::class, 'show'])->name('adjustmenttypes.show');
    });

    Route::middleware(['permission:wmsinventory.create-adjustment-type'])->group(function () {
        Route::post('/adjustment-types', [AdjustmentTypeController::class, 'store'])->name('adjustmenttypes.store');
    });

    Route::middleware(['permission:wmsinventory.edit-adjustment-type'])->group(function () {
        Route::put('/adjustment-types/{adjustmentType}', [AdjustmentTypeController::class, 'update'])->name('adjustmenttypes.update');
        Route::patch('/adjustment-types/{adjustmentType}', [AdjustmentTypeController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.delete-adjustment-type'])->group(function () {
        Route::delete('/adjustment-types/{adjustmentType}', [AdjustmentTypeController::class, 'destroy'])->name('adjustmenttypes.destroy');
    });

    // Transfers - grouped by permission type
    // Create routes must come before parameterized routes to avoid 404 errors
    Route::middleware(['permission:wmsinventory.create-transfer'])->group(function () {
        Route::get('/transfers/create', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    });

    Route::middleware(['permission:wmsinventory.view-transfers'])->group(function () {
        Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/data', [TransferController::class, 'getDataAjax'])->name('transfers.data');
        Route::get('/transfers/warehouse-products', [TransferController::class, 'getWarehouseProducts'])->name('transfers.warehouse-products');
        Route::get('/transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
    });

    Route::middleware(['permission:wmsinventory.edit-transfer'])->group(function () {
        Route::get('/transfers/{transfer}/edit', [TransferController::class, 'edit'])->name('transfers.edit');
        Route::put('/transfers/{transfer}', [TransferController::class, 'update'])->name('transfers.update');
        Route::patch('/transfers/{transfer}', [TransferController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.approve-transfer'])->group(function () {
        Route::post('/transfers/{transfer}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
    });

    Route::middleware(['permission:wmsinventory.ship-transfer'])->group(function () {
        Route::post('/transfers/{transfer}/ship', [TransferController::class, 'ship'])->name('transfers.ship');
    });

    Route::middleware(['permission:wmsinventory.receive-transfer'])->group(function () {
        Route::post('/transfers/{transfer}/receive', [TransferController::class, 'receive'])->name('transfers.receive');
    });

    Route::middleware(['permission:wmsinventory.cancel-transfer'])->group(function () {
        Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
    });

    Route::middleware(['permission:wmsinventory.delete-transfer'])->group(function () {
        Route::delete('/transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');
    });

    // Vendors - grouped by permission type
    Route::middleware(['permission:wmsinventory.view-vendors'])->group(function () {
        Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/data', [VendorController::class, 'getDataAjax'])->name('vendors.data');
    });

    Route::middleware(['permission:wmsinventory.search-vendors'])->group(function () {
        Route::get('/vendors/search', [VendorController::class, 'searchVendors'])->name('vendors.search');
    });

    Route::middleware(['permission:wmsinventory.create-vendor'])->group(function () {
        Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
    });

    Route::middleware(['permission:wmsinventory.edit-vendor'])->group(function () {
        Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::patch('/vendors/{vendor}', [VendorController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.view-vendors'])->group(function () {
        Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
    });

    Route::middleware(['permission:wmsinventory.delete-vendor'])->group(function () {
        Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
    });

    // Purchase Orders - grouped by permission type
    Route::middleware(['permission:wmsinventory.view-purchases'])->group(function () {
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/data', [PurchaseController::class, 'getDataAjax'])->name('purchases.data');
        Route::get('/vendors/{vendor}/products', [PurchaseController::class, 'getVendorProducts'])->name('purchases.vendor-products');
    });

    Route::middleware(['permission:wmsinventory.create-purchase'])->group(function () {
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    });

    Route::middleware(['permission:wmsinventory.edit-purchase'])->group(function () {
        Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
        Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::patch('/purchases/{purchase}', [PurchaseController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.view-purchases'])->group(function () {
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('/purchases/{purchase}/pdf', [PurchaseController::class, 'generatePDF'])->name('purchases.pdf');
    });

    Route::middleware(['permission:wmsinventory.create-purchase'])->group(function () {
        Route::post('/purchases/{purchase}/duplicate', [PurchaseController::class, 'duplicate'])->name('purchases.duplicate');
    });

    Route::middleware(['permission:wmsinventory.delete-purchase'])->group(function () {
        Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');
    });

    Route::middleware(['permission:wmsinventory.approve-purchase'])->group(function () {
        Route::post('/purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
        Route::post('/purchases/{purchase}/reject', [PurchaseController::class, 'reject'])->name('purchases.reject');
    });

    Route::middleware(['permission:wmsinventory.edit-purchase'])->group(function () {
        Route::post('/purchases/{purchase}/update-payment-status', [PurchaseController::class, 'updatePaymentStatus'])->name('purchases.update-payment-status');
    });

    Route::middleware(['permission:wmsinventory.receive-purchase'])->group(function () {
        Route::get('/purchases/{purchase}/receive', [PurchaseController::class, 'showReceive'])->name('purchases.show-receive');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::post('/purchases/{purchase}/receive-partial', [PurchaseController::class, 'receivePartial'])->name('purchases.receive-partial');
    });

    // Customers - Customer search functionality for sales
    Route::middleware(['permission:wmsinventory.search-customers'])->group(function () {
        Route::get('/customers/search', [SaleController::class, 'searchCustomers'])->name('customers.search');
    });

    // Sales Orders - grouped by permission type
    Route::middleware(['permission:wmsinventory.view-sales'])->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/data', [SaleController::class, 'getDataAjax'])->name('sales.data');
        Route::get('/customers/{customer}/products', [SaleController::class, 'getCustomerProducts'])->name('sales.customer-products');
    });

    Route::middleware(['permission:wmsinventory.create-sale'])->group(function () {
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    });

    Route::middleware(['permission:wmsinventory.edit-sale'])->group(function () {
        Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->name('sales.edit');
        Route::put('/sales/{sale}', [SaleController::class, 'update'])->name('sales.update');
        Route::patch('/sales/{sale}', [SaleController::class, 'update']);
    });

    Route::middleware(['permission:wmsinventory.view-sales'])->group(function () {
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{sale}/pdf', [SaleController::class, 'generatePDF'])->name('sales.pdf');
    });

    Route::middleware(['permission:wmsinventory.create-sale'])->group(function () {
        Route::post('/sales/{sale}/duplicate', [SaleController::class, 'duplicate'])->name('sales.duplicate');
    });

    Route::middleware(['permission:wmsinventory.delete-sale'])->group(function () {
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    });

    Route::middleware(['permission:wmsinventory.approve-sale'])->group(function () {
        Route::post('/sales/{sale}/approve', [SaleController::class, 'approve'])->name('sales.approve');
        Route::post('/sales/{sale}/reject', [SaleController::class, 'reject'])->name('sales.reject');
    });

    Route::middleware(['permission:wmsinventory.fulfill-sale'])->group(function () {
        Route::get('/sales/{sale}/fulfill', [SaleController::class, 'showFulfill'])->name('sales.show-fulfill');
        Route::post('/sales/{sale}/fulfill', [SaleController::class, 'fulfill'])->name('sales.fulfill');
        Route::post('/sales/{sale}/fulfill-partial', [SaleController::class, 'fulfillPartial'])->name('sales.fulfill-partial');
    });

    Route::middleware(['permission:wmsinventory.ship-sale'])->group(function () {
        Route::post('/sales/{sale}/ship', [SaleController::class, 'ship'])->name('sales.ship');
    });

    Route::middleware(['permission:wmsinventory.deliver-sale'])->group(function () {
        Route::post('/sales/{sale}/deliver', [SaleController::class, 'deliver'])->name('sales.deliver');
    });

    Route::middleware(['permission:wmsinventory.generate-invoice'])->group(function () {
        Route::post('/sales/{sale}/generate-invoice', [SaleController::class, 'generateInvoice'])->name('sales.generate-invoice');
    });

    // Reports - grouped by permission type
    Route::middleware(['permission:wmsinventory.view-inventory-valuation'])->group(function () {
        Route::get('/reports/inventory-valuation', [ReportController::class, 'inventoryValuation'])->name('reports.inventory-valuation');
    });

    Route::middleware(['permission:wmsinventory.view-stock-movement'])->group(function () {
        Route::get('/reports/stock-movement', [ReportController::class, 'stockMovement'])->name('reports.stock-movement');
    });

    Route::middleware(['permission:wmsinventory.view-low-stock'])->group(function () {
        Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
    });
});
