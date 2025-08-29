<?php

use Illuminate\Support\Facades\Route;
use Modules\AICore\Http\Controllers\AIDashboardController;
use Modules\AICore\Http\Controllers\AIModelController;
use Modules\AICore\Http\Controllers\AIModuleConfigurationController;
use Modules\AICore\Http\Controllers\AIProviderController;
use Modules\AICore\Http\Controllers\AIRequestLogController;
use Modules\AICore\Http\Controllers\AISettingsController;
use Modules\AICore\Http\Controllers\AIUsageController;

/*
|--------------------------------------------------------------------------
| AI Core Web Routes
|--------------------------------------------------------------------------
|
| These routes provide web interface access to AI management functionality.
| All routes require authentication and appropriate permissions.
|
*/

Route::middleware(['auth', 'verified'])->prefix('aicore')->name('aicore.')->group(function () {

    // AI Dashboard
    Route::get('/dashboard', [AIDashboardController::class, 'index'])->name('dashboard');

    // AI Providers Management
    Route::resource('providers', AIProviderController::class)->names('providers');
    Route::post('providers/{provider}/test', [AIProviderController::class, 'testConnection'])->name('providers.test');

    // AI Models Management
    Route::resource('models', AIModelController::class)->names('models');
    Route::post('models/{model}/test', [AIModelController::class, 'test'])->name('models.test');

    // Usage Analytics
    Route::get('usage', [AIUsageController::class, 'index'])->name('usage.index');
    Route::get('usage/export', [AIUsageController::class, 'export'])->name('usage.export');
    Route::get('usage/{id}', [AIUsageController::class, 'show'])->name('usage.show');

    // AI Settings (Standard Settings System)
    Route::get('settings', [AISettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [AISettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/reset', [AISettingsController::class, 'reset'])->name('settings.reset');

    // Module-Model Mapping Configuration
    Route::prefix('module-configuration')->name('module-configuration.')->group(function () {
        Route::get('/', [AIModuleConfigurationController::class, 'index'])->name('index');
        Route::put('/{id}', [AIModuleConfigurationController::class, 'update'])->name('update');
        Route::put('/{id}/ajax', [AIModuleConfigurationController::class, 'updateAjax'])->name('update.ajax');
        Route::post('/sync', [AIModuleConfigurationController::class, 'syncModules'])->name('sync');
        Route::get('/provider/{providerId}/models', [AIModuleConfigurationController::class, 'getProviderModels'])->name('provider.models');
        Route::post('/{id}/toggle-status', [AIModuleConfigurationController::class, 'toggleStatus'])->name('toggle-status');
    });

    // AI Request Logs (Admin only)
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [AIRequestLogController::class, 'index'])->name('index');
        Route::get('/datatable', [AIRequestLogController::class, 'indexAjax'])->name('datatable');
        Route::get('/statistics', [AIRequestLogController::class, 'statistics'])->name('statistics');
        Route::get('/export', [AIRequestLogController::class, 'export'])->name('export');
        Route::get('/{id}', [AIRequestLogController::class, 'show'])->name('show');
        Route::post('/{id}/flag', [AIRequestLogController::class, 'toggleFlag'])->name('flag');
        Route::post('/{id}/review', [AIRequestLogController::class, 'review'])->name('review');
    });
});
