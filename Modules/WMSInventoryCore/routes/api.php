<?php

use Illuminate\Support\Facades\Route;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\V1\AdjustmentController;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\V1\CategoryController;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\V1\InventoryController;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\V1\ProductController;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\V1\TransferController;
use Modules\WMSInventoryCore\app\Http\Controllers\Api\V1\WarehouseController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware('api')->prefix('wmsinventorycore')->group(function () {

    Route::group(['prefix' => 'v1'], function () {

        // Product API routes
        Route::prefix('products')->name('products.')->group(function () {
            // Special endpoints (must come before resource routes)
            Route::get('/search', [ProductController::class, 'search'])->name('search');
            Route::get('/barcode-lookup', [ProductController::class, 'barcodeLookup'])->name('barcode_lookup');
            Route::get('/low-stock', [ProductController::class, 'lowStock'])->name('low_stock');
            Route::get('/statistics', [ProductController::class, 'statistics'])->name('statistics');
            Route::post('/batch-operation', [ProductController::class, 'batchOperation'])->name('batch_operation');

            // Standard CRUD operations
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/{product}', [ProductController::class, 'show'])->name('show');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::patch('/{product}', [ProductController::class, 'update'])->name('patch');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');

            // Product-specific endpoints
            Route::get('/{product}/inventory', [ProductController::class, 'inventory'])->name('inventory');
            Route::get('/{product}/stock-levels', [ProductController::class, 'stockLevels'])->name('stock_levels');
            Route::get('/{product}/movements', [ProductController::class, 'movements'])->name('movements');
        });

        // Warehouse API routes
        Route::prefix('warehouses')->name('warehouses.')->group(function () {
            // Standard CRUD operations
            Route::get('/', [WarehouseController::class, 'index'])->name('index');
            Route::post('/', [WarehouseController::class, 'store'])->name('store');
            Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
            Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
            Route::patch('/{warehouse}', [WarehouseController::class, 'update'])->name('patch');
            Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');

            // Warehouse-specific endpoints
            Route::get('/{warehouse}/inventory', [WarehouseController::class, 'inventory'])->name('inventory');
            Route::get('/{warehouse}/zones', [WarehouseController::class, 'zones'])->name('zones');
            Route::get('/{warehouse}/statistics', [WarehouseController::class, 'statistics'])->name('statistics');
            Route::get('/{warehouse}/stock-valuation', [WarehouseController::class, 'stockValuation'])->name('stock_valuation');
            Route::get('/{warehouse}/capacity-utilization', [WarehouseController::class, 'capacityUtilization'])->name('capacity_utilization');
            Route::get('/{warehouse}/transfer-history', [WarehouseController::class, 'transferHistory'])->name('transfer_history');
        });

        // Category API routes
        Route::prefix('categories')->name('categories.')->group(function () {
            // Special endpoints
            Route::get('/tree', [CategoryController::class, 'tree'])->name('tree');
            Route::get('/search', [CategoryController::class, 'search'])->name('search');
            Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');

            // Standard CRUD operations
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::patch('/{category}', [CategoryController::class, 'update'])->name('patch');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');

            // Category-specific endpoints
            Route::get('/{category}/products', [CategoryController::class, 'products'])->name('products');
            Route::get('/{category}/statistics', [CategoryController::class, 'statistics'])->name('statistics');
            Route::post('/{category}/move', [CategoryController::class, 'move'])->name('move');
        });

        // Inventory API routes
        Route::prefix('inventory')->name('inventory.')->group(function () {
            // Special endpoints
            Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low_stock');
            Route::get('/out-of-stock', [InventoryController::class, 'outOfStock'])->name('out_of_stock');
            Route::get('/valuation', [InventoryController::class, 'valuation'])->name('valuation');
            Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions');
            Route::get('/alerts', [InventoryController::class, 'alerts'])->name('alerts');
            Route::get('/statistics', [InventoryController::class, 'statistics'])->name('statistics');
            Route::post('/reserve', [InventoryController::class, 'reserve'])->name('reserve');
            Route::post('/release', [InventoryController::class, 'release'])->name('release');

            // Standard operations
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/{product}/{warehouse?}', [InventoryController::class, 'show'])->name('show');
            Route::put('/{product}/{warehouse}', [InventoryController::class, 'update'])->name('update');
            Route::get('/{product}/stock-levels', [InventoryController::class, 'stockLevels'])->name('stock_levels');
        });

        // Transfer API routes
        Route::prefix('transfers')->name('transfers.')->group(function () {
            // Special endpoints
            Route::get('/search', [TransferController::class, 'search'])->name('search');
            Route::get('/statistics', [TransferController::class, 'statistics'])->name('statistics');

            // Standard CRUD operations
            Route::get('/', [TransferController::class, 'index'])->name('index');
            Route::post('/', [TransferController::class, 'store'])->name('store');
            Route::get('/{transfer}', [TransferController::class, 'show'])->name('show');
            Route::put('/{transfer}', [TransferController::class, 'update'])->name('update');
            Route::patch('/{transfer}', [TransferController::class, 'update'])->name('patch');
            Route::delete('/{transfer}', [TransferController::class, 'destroy'])->name('destroy');

            // Transfer actions
            Route::post('/{transfer}/approve', [TransferController::class, 'approve'])->name('approve');
            Route::post('/{transfer}/ship', [TransferController::class, 'ship'])->name('ship');
            Route::post('/{transfer}/receive', [TransferController::class, 'receive'])->name('receive');
            Route::post('/{transfer}/cancel', [TransferController::class, 'cancel'])->name('cancel');
        });

        // Adjustment API routes
        Route::prefix('adjustments')->name('adjustments.')->group(function () {
            // Special endpoints
            Route::get('/search', [AdjustmentController::class, 'search'])->name('search');
            Route::get('/statistics', [AdjustmentController::class, 'statistics'])->name('statistics');
            Route::get('/reasons', [AdjustmentController::class, 'reasons'])->name('reasons');

            // Standard CRUD operations
            Route::get('/', [AdjustmentController::class, 'index'])->name('index');
            Route::post('/', [AdjustmentController::class, 'store'])->name('store');
            Route::get('/{adjustment}', [AdjustmentController::class, 'show'])->name('show');
            Route::put('/{adjustment}', [AdjustmentController::class, 'update'])->name('update');
            Route::patch('/{adjustment}', [AdjustmentController::class, 'update'])->name('patch');
            Route::delete('/{adjustment}', [AdjustmentController::class, 'destroy'])->name('destroy');

            // Adjustment actions
            Route::post('/{adjustment}/submit', [AdjustmentController::class, 'submit'])->name('submit');
            Route::post('/{adjustment}/approve', [AdjustmentController::class, 'approve'])->name('approve');
            Route::post('/{adjustment}/reject', [AdjustmentController::class, 'reject'])->name('reject');
        });

    }); // End v1 group
}); // End wmsinventory group
