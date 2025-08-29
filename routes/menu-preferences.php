<?php

use App\Http\Controllers\MenuPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/menu-preferences/toggle-pin', [MenuPreferenceController::class, 'togglePin'])->name('menu-preferences.toggle-pin');
    Route::post('/menu-preferences/update-order', [MenuPreferenceController::class, 'updateOrder'])->name('menu-preferences.update-order');
    Route::get('/menu-preferences/pinned', [MenuPreferenceController::class, 'getPinned'])->name('menu-preferences.get-pinned');
});
