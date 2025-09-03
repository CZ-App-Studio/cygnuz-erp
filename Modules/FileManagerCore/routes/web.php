<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManagerCore\app\Http\Controllers\Settings\FileManagerCoreSettingsController;

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

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::prefix('filemanagercore')->name('filemanagercore.')->group(function () {

        // File access routes
        Route::get('/file/{uuid}/download', [\Modules\FileManagerCore\app\Http\Controllers\FileController::class, 'download'])->name('file.download');
        Route::get('/file/{uuid}/view', [\Modules\FileManagerCore\app\Http\Controllers\FileController::class, 'view'])->name('file.view');

        // Settings routes
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [FileManagerCoreSettingsController::class, 'index'])->name('index');
            Route::post('/update', [FileManagerCoreSettingsController::class, 'update'])->name('update');
            Route::post('/reset', [FileManagerCoreSettingsController::class, 'resetToDefaults'])->name('reset');
            Route::post('/test', [FileManagerCoreSettingsController::class, 'testConfiguration'])->name('test');
            Route::get('/stats', [FileManagerCoreSettingsController::class, 'getStorageStats'])->name('stats');
            Route::get('/get/{key}', [FileManagerCoreSettingsController::class, 'getSetting'])->name('get');
        });
    });
});
