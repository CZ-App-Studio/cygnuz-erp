<?php

use Illuminate\Support\Facades\Route;
use Modules\AiChat\Http\Controllers\AiChatController;

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

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('aichat')->name('aichat.')->group(function () {
        // Chat endpoints
        Route::post('/chat', [AiChatController::class, 'chat'])->name('chat');
        Route::post('/query', [AiChatController::class, 'handleQuery'])->name('query');
        Route::post('/complete', [AiChatController::class, 'complete'])->name('complete');
        Route::post('/summarize', [AiChatController::class, 'summarize'])->name('summarize');
        Route::post('/extract', [AiChatController::class, 'extract'])->name('extract');
        
        // Schema and testing
        Route::get('/schema', [AiChatController::class, 'getSchema'])->name('schema');
        Route::get('/test', [AiChatController::class, 'test'])->name('test');
        
        // Usage and analytics
        Route::get('/usage', [AiChatController::class, 'usage'])->name('usage');
        
        // Provider management
        Route::get('/providers', [AiChatController::class, 'getProviders'])->name('providers');
        Route::post('/providers/test', [AiChatController::class, 'testProvider'])->name('providers.test');
    });
});
