<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemCore\app\Http\Controllers\Api\V1\AuthController;
use Modules\SystemCore\app\Http\Controllers\Api\V1\SettingsController;

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

// Version 1 API Routes for Mobile App
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Authentication routes (public routes)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

        // Protected auth routes
        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');
        });
    });

    // Settings routes (JWT authentication for mobile app)
    Route::middleware(['auth:api'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('basic', [SettingsController::class, 'getBasicSettings'])->name('basic');
        Route::get('modules', [SettingsController::class, 'getModuleSettings'])->name('modules');
        Route::get('all', [SettingsController::class, 'getAllSettings'])->name('all');
        Route::post('update-preference', [SettingsController::class, 'updateUserPreferences'])->name('update-preference');
    });
});
