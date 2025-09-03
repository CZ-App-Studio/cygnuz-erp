<?php

use App\Http\Controllers\MasterData\ImportExportController;
use App\Http\Controllers\MasterData\MasterDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Master Data Routes
|--------------------------------------------------------------------------
|
| Here are the routes for master data management functionality.
| These routes provide centralized access to all master data across modules.
|
*/

Route::middleware(['auth', 'web'])->prefix('master-data')->name('master-data.')->group(function () {

    // Master Data Dashboard
    Route::get('/', [MasterDataController::class, 'index'])->name('index');

    // Import/Export Routes (conditional - only if DataImportExport addon is available)
    Route::prefix('import-export')->name('import-export.')->group(function () {
        Route::get('/', [ImportExportController::class, 'index'])->name('index');
        Route::get('/template', [ImportExportController::class, 'getTemplate'])->name('template');
        Route::post('/import', [ImportExportController::class, 'import'])->name('import');
        Route::post('/export', [ImportExportController::class, 'export'])->name('export');
        Route::get('/status', [ImportExportController::class, 'status'])->name('status');
    });

    // Quick export route for DataTables and other components
    Route::post('/quick-export/{type}', function ($type) {
        $controller = app(ImportExportController::class);

        return $controller->export(request()->merge(['type' => $type]));
    })->name('quick-export');

});
