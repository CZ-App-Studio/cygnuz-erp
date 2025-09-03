<?php

use Illuminate\Support\Facades\Route;
use Modules\Calendar\app\Http\Controllers\EventController;

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

Route::group([
    'middleware' => function ($request, $next) {
        $request->headers->set('addon', ModuleConstants::CALENDAR);

        return $next($request);
    },
], function () {
    Route::middleware([
        'api',
        'auth',
    ])->group(function () {

        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [EventController::class, 'index'])->name('index');

            // AJAX routes for FullCalendar
            Route::get('/events', [EventController::class, 'eventsAjax'])->name('events.ajax');
            Route::post('/events', [EventController::class, 'store'])->name('events.store');
            Route::put('/events/{id}', [EventController::class, 'update'])->name('events.update'); // Use PUT for update
            Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('events.destroy'); // Use DELETE for destroy
            Route::get('/events/{id}/details', [EventController::class, 'getEventAjax'])->name('events.details.ajax');

            Route::get('/events/searchClientsAjax', [EventController::class, 'searchClientsAjax'])->name('events.searchClientsAjax');
            Route::get('/events/searchRelatedEntities', [EventController::class, 'searchRelatedEntities'])->name('events.searchRelatedEntities');
        });
    });
});
