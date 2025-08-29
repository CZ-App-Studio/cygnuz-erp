<?php

use Illuminate\Support\Facades\Route;
use Modules\Announcement\app\Http\Controllers\AnnouncementController;

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

Route::middleware(['auth', 'verified'])->group(function () {
    // Main announcement routes
    Route::resource('announcements', AnnouncementController::class);
    
    // Additional announcement routes
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::post('{announcement}/acknowledge', [AnnouncementController::class, 'acknowledge'])->name('acknowledge');
        Route::post('{announcement}/toggle-pin', [AnnouncementController::class, 'togglePin'])->name('toggle-pin');
        Route::get('my/list', [AnnouncementController::class, 'myAnnouncements'])->name('my');
    });
});