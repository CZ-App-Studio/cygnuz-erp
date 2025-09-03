<?php

use App\Http\Middleware\AddonCheckMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\AiChat\Http\Controllers\AiChatController;
use Modules\AiChat\Http\Controllers\AIChatEnhancedController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Enhanced AI Chat Module Routes with chat history, better UI/UX,
| user role awareness and proper AICore integration
|
*/

Route::group(['middleware' => function ($request, $next) {
    $request->headers->set('addon', ModuleConstants::AI_CHAT);

    return $next($request);
}], function () {
    Route::middleware([
        'web',
        'auth',
        AddonCheckMiddleware::class,
    ])->group(function () {
        // Enhanced Chat Interface Routes
        Route::prefix('aichat')->name('aichat.')->group(function () {
            // Main chat interface
            Route::get('/', [AIChatEnhancedController::class, 'index'])->name('index');

            // Chat management
            Route::post('/create', [AIChatEnhancedController::class, 'createChat'])->name('create');
            Route::post('/send', [AIChatEnhancedController::class, 'sendMessage'])->name('send');
            Route::get('/history/{chatId}', [AIChatEnhancedController::class, 'getChatHistory'])->name('history');
            Route::delete('/chat/{chatId}', [AIChatEnhancedController::class, 'deleteChat'])->name('delete');
            Route::post('/chat/{chatId}/archive', [AIChatEnhancedController::class, 'archiveChat'])->name('archive');
            Route::post('/chat/{chatId}/clear', [AIChatEnhancedController::class, 'clearChat'])->name('clear');
            Route::get('/chat/{chatId}/export', [AIChatEnhancedController::class, 'exportChat'])->name('export');

            // Search and statistics
            Route::get('/search', [AIChatEnhancedController::class, 'searchChats'])->name('search');
            Route::get('/statistics', [AIChatEnhancedController::class, 'getChatStatistics'])->name('statistics');

            // Message actions
            Route::post('/message/{messageId}/pin', [AIChatEnhancedController::class, 'toggleMessagePin'])->name('message.pin');

            // API endpoints using AICore
            Route::post('/api/chat', [AiChatController::class, 'chat'])->name('api.chat');
            Route::post('/api/complete', [AiChatController::class, 'complete'])->name('api.complete');
            Route::post('/api/summarize', [AiChatController::class, 'summarize'])->name('api.summarize');
            Route::post('/api/extract', [AiChatController::class, 'extract'])->name('api.extract');
            Route::get('/api/usage', [AiChatController::class, 'usage'])->name('api.usage');
            Route::get('/api/providers', [AiChatController::class, 'getProviders'])->name('api.providers');
            Route::post('/api/test-provider', [AiChatController::class, 'testProvider'])->name('api.test-provider');
        });

        // Legacy routes (kept for backward compatibility)
        Route::prefix('aiChat')->group(function () {
            Route::get('/', function () {
                return redirect()->route('aichat.index');
            });
            Route::get('/test-api', function () {
                return view('aichat::test-api');
            })->name('aiChat.test-api');
            Route::post('/query', [AiChatController::class, 'handleQuery']);
            Route::get('/test', [AiChatController::class, 'test']);
            Route::get('/getSchema', [AiChatController::class, 'getSchema']);
        });
    });
});
