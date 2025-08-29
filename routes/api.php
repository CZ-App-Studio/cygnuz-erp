<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserSettingsController;
use Illuminate\Support\Facades\Route;
use Modules\HRCore\Http\Controllers\Api\AttendanceController;


Route::middleware([
  'api'
])->group(function () {
  Route::middleware('api')->group(function () {
    Route::group(['prefix' => 'V1'], function () {
      Route::get('checkDemoMode', [BaseApiController::class, 'checkDemoMode'])->name('api.base.checkDemoMode');
    });
  });
});


// Publicly accessible routes
Route::middleware('api')->group(function () {

  Route::group(['prefix' => 'V1'], function () {

    Route::get('hello', function () {
      return response()->json(['message' => 'Hello World!']);
    });

    Route::post('checkUsername', [AuthController::class, 'checkEmail'])->name('api.auth.checkUserName');

    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('loginWithUid', [AuthController::class, 'loginWithUid'])->name('loginWithUid');
    Route::post('createDemoUser', [AuthController::class, 'createDemoUser'])->name('createDemoUser');

    //Open Auth Routes
    Route::group(['prefix' => 'auth/'], function () {

      Route::get('refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
    });
  });


});

// Protected routes

Route::middleware('auth:api')->group(function () {
  Route::group([
    'middleware' => 'api',
    'as' => 'api.',
  ], function ($router) {
    Route::group(['prefix' => 'V1/'], function () {

      //Authentication
      Route::group(['prefix' => 'auth/'], function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('changePassword', [AuthController::class, 'changePassword'])->name('changePassword');
      });

      Route::prefix('userSettings/')->name('userSettings.')->group(function () {
        Route::get('getAll', [UserSettingsController::class, 'getAll'])->name('getAll');
        Route::post('getByKey', [UserSettingsController::class, 'getByKey'])->name('getByKey');
        Route::post('addOrUpdate', [UserSettingsController::class, 'addOrUpdate'])->name('addOrUpdate');
        Route::delete('delete', [UserSettingsController::class, 'delete'])->name('delete');
      });

      //Account
      Route::group(['prefix' => 'account/'], function () {
        Route::get('me', [AccountController::class, 'me'])->name('me');
        Route::get('getAccountStatus', [AccountController::class, 'getAccountStatus'])->name('getAccountStatus');
        Route::get('getProfilePicture', [AccountController::class, 'getProfilePicture'])->name('getProfilePicture');
        Route::post('updateProfilePicture', [AccountController::class, 'updateProfilePicture'])->name('updateProfilePicture');
        Route::get('getLanguage', [AccountController::class, 'getLanguage'])->name('getLanguage');
        Route::post('updateLanguage', [AccountController::class, 'updateLanguage'])->name('updateLanguage');
        Route::post('updateProfile', [AccountController::class, 'updateProfile'])->name('updateProfile');
        Route::post('deleteAccountRequest', [AccountController::class, 'deleteAccountRequest'])->name('deleteAccountRequest');
      });


      //Device Controller
      Route::get('checkDevice', [DeviceController::class, 'checkDevice'])->name('checkDevice');
      Route::get('validateDevice', [DeviceController::class, 'validateDevice'])->name('validateDevice');
      Route::post('registerDevice', [DeviceController::class, 'registerDevice'])->name('registerDevice');
      Route::post('messagingToken', [DeviceController::class, 'messagingToken'])->name('messagingToken');
      Route::post('updateDeviceStatus', [DeviceController::class, 'updateDeviceStatus'])->name('updateDeviceStatus');


      //User
      Route::group(['prefix', 'user'], function () {
        Route::get('user/search/{query}', [UserController::class, 'searchUsers'])->name('searchUsers');
        Route::get('user/getAll', [UserController::class, 'getUsersList'])->name('getAllUsers');
        Route::get('userStatus', [UserController::class, 'getUserStatus'])->name('getUserStatus');
        Route::get('user/{id}', [UserController::class, 'getUserInfo'])->name('getUserInfo');
        Route::post('user/updateStatus', [UserController::class, 'updateUserStatus'])->name('updateUserStatus');
      });


      //Notification
      Route::group(['prefix' => 'notification'], function () {
        Route::get('getAll', [NotificationController::class, 'getAll'])->name('getAll');
        Route::post('markAsRead/{id}', [NotificationController::class, 'markAsRead'])->name('markAsRead');
      });



    });
  });
});
