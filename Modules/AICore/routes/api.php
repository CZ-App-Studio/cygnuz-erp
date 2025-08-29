<?php

use Illuminate\Support\Facades\Route;
use Modules\AICore\Http\Controllers\Api\AIController;
use Modules\AICore\Http\Controllers\Api\AIProviderApiController;
use Modules\AICore\Http\Controllers\Api\AIUsageApiController;
use Modules\AICore\Http\Middleware\AIRateLimitMiddleware;

/*
|--------------------------------------------------------------------------
| AI Core API Routes
|--------------------------------------------------------------------------
|
| These routes provide API access to AI functionality for other modules
| and external integrations. All routes require authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('ai')->group(function () {

    // Core AI Operations (with rate limiting)
    Route::middleware([AIRateLimitMiddleware::class])->group(function () {
        Route::post('/chat', [AIController::class, 'chat'])->name('ai.chat');
        Route::post('/complete', [AIController::class, 'complete'])->name('ai.complete');
        Route::post('/summarize', [AIController::class, 'summarize'])->name('ai.summarize');
        Route::post('/extract', [AIController::class, 'extract'])->name('ai.extract');
    });

    // Usage and Analytics
    Route::get('/usage', [AIController::class, 'usage'])->name('ai.usage');

    // Provider Management (Admin only)
    Route::middleware(['role:admin|super_admin'])->prefix('admin')->group(function () {
        Route::apiResource('providers', AIProviderApiController::class);
        Route::post('providers/{provider}/test', [AIProviderApiController::class, 'testConnection']);
        Route::get('providers/{provider}/usage', [AIProviderApiController::class, 'getUsage']);

        // Usage Analytics
        Route::get('usage/reports', [AIUsageApiController::class, 'reports']);
        Route::get('usage/trends', [AIUsageApiController::class, 'trends']);
    });
});
