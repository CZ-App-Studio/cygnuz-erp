<?php

use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'web'])->prefix('api/menu')->name('menu.')->group(function () {
    // Menu search and discovery
    Route::post('search', [MenuController::class, 'search'])->name('search');

    // User favorites
    Route::get('favorites', [MenuController::class, 'getFavorites'])->name('favorites');
    Route::post('favorites', [MenuController::class, 'addFavorite'])->name('favorites.add');

    // Recently accessed
    Route::get('recent', [MenuController::class, 'getRecent'])->name('recent');
    Route::post('track-access', [MenuController::class, 'trackAccess'])->name('track-access');

    // Menu profiles
    Route::get('profiles', [MenuController::class, 'getProfiles'])->name('profiles');
    Route::post('profiles/switch', [MenuController::class, 'switchProfile'])->name('profiles.switch');

    // Admin functions
    Route::post('refresh-cache', [MenuController::class, 'refreshCache'])->name('refresh-cache');
    Route::get('statistics', [MenuController::class, 'getStatistics'])->name('statistics');

    // User preferences (existing functionality)
    Route::post('toggle-pin', [MenuPreferenceController::class, 'togglePin'])->name('toggle-pin');
    Route::post('update-order', [MenuPreferenceController::class, 'updateOrder'])->name('update-order');
    Route::get('pinned', [MenuPreferenceController::class, 'getPinned'])->name('pinned');
});
