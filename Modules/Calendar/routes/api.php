<?php

use Illuminate\Support\Facades\Route;
use Modules\Calendar\app\Http\Controllers\Api\EventApiController;

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

Route::middleware([
    'api',
])->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::group(['prefix' => 'V1'], function () {
            Route::group([
                'middleware' => 'api',
                'as' => 'api.',
            ], function () {

                Route::prefix('events')->name('api.events.')->group(function () {
                    Route::get('/', [EventApiController::class, 'getAll'])->name('getAll');
                    Route::post('/', [EventApiController::class, 'create'])->name('create');
                    Route::post('update/{id}', [EventApiController::class, 'update'])->name('update'); // Standard REST uses PUT/PATCH
                    Route::post('/{id}', [EventApiController::class, 'delete'])->name('delete');
                    // Add GET /{id} if needed to fetch a single event detail via API
                });
            });
        });
    });
});
