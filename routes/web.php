<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\DashboardController;
// use App\Http\Controllers\EmployeeController; // Moved to HRCore module
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
*/

require __DIR__.'/auth.php';
require __DIR__.'/user.php';
require __DIR__.'/menu-preferences.php';
require __DIR__.'/menu.php';
require __DIR__.'/master-data.php';

Route::middleware(['web'])->group(function () {
    Route::get('/auth/login', [AuthController::class, 'login'])->name('login');
    Route::post('/auth/login', [AuthController::class, 'loginPost'])->name('auth.loginPost');
    Route::get('/accessDenied', [BaseController::class, 'accessDenied'])->name('accessDenied');
});

// TODO: Restrict to super admin
Route::middleware('auth')->group(callback: function () {
    // Addon Routes
    if (config('custom.custom.displayAddon')) {
        Route::get('/addons', [AddonController::class, 'index'])->name('addons.index');
        Route::post('/addons/activate', [AddonController::class, 'activate'])->name('module.activate');
        Route::post('/addons/deactivate', [AddonController::class, 'deactivate'])->name('module.deactivate');
        Route::post('/addons/upload', [AddonController::class, 'upload'])->name('module.upload');
        Route::post('/addons/update', [AddonController::class, 'update'])->name('module.update');
        Route::delete('/addons/uninstall', [AddonController::class, 'uninstall'])->name('module.uninstall');
    }
});

Route::middleware([
    'web',
])->group(function () {

    // Root route - Check if landing page is enabled
    Route::get('/', function () {
        // Check if we're in tenant context (only if MultiTenancyCore is enabled)
        if (function_exists('is_tenant_context') && is_tenant_context()) {
            // For tenants, always redirect to login or dashboard
            if (auth()->check()) {
                return redirect()->route('dashboard');
            }

            return redirect()->route('login');
        }

        // Main application logic (non-tenant)
        $addonService = app(\App\Services\AddonService\IAddonService::class);

        // If user is authenticated, redirect based on role
        if (auth()->check()) {
            $user = auth()->user();

            // Check if user has tenant role
            if ($user->hasRole('tenant')) {
                return redirect()->route('multitenancycore.tenant.dashboard');
            }

            // Otherwise go to regular dashboard
            return redirect()->route('dashboard');
        }

        // If landing page is enabled, show landing page
        if ($addonService->isAddonEnabled('LandingPage')) {
            $controller = app()->make(\Modules\LandingPage\app\Http\Controllers\LandingPageController::class);

            return $controller->index();
        }

        // Otherwise, redirect to login
        return redirect()->route('login');
    })->name('home');

    Route::middleware('auth')->group(callback: function () {

        // Dashboard Route
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('support', [SupportController::class, 'index'])->name('support.index');

        Route::get('userssss/select-search', [UserController::class, 'searchActiveUsersForSelect'])->name('users.selectSearch');

        // Notification Routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/my', [NotificationController::class, 'myNotifications'])->name('myNotifications');
            Route::post('/mark-as-read/{id?}', [NotificationController::class, 'markAsRead'])->name('markAsRead');
            Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
            Route::delete('/{id}', [NotificationController::class, 'delete'])->name('delete');
            Route::get('/ajax', [NotificationController::class, 'getNotificationsAjax'])->name('getNotificationsAjax');
        });

        Route::get('getAttendanceLogAjax/{userId}/{date}', [DashboardController::class, 'getAttendanceLogAjax'])->name('getAttendanceLogAjax');
        Route::get('getStatsForTimeLineAjax/{userId}/{date}/{attendanceLogId}', [DashboardController::class, 'getStatsForTimeLineAjax'])->name('getStatsForTimeLineAjax');
        Route::get('getActivityAjax/{userId}/{date}/{attendanceLogId}', [DashboardController::class, 'getActivityAjax'])->name('getActivityAjax');
        Route::get('getDeviceLocationAjax/{userId}/{date}/{attendanceLogId}', [DashboardController::class, 'getDeviceLocationAjax'])->name('getDeviceLocationAjax');

        Route::get('getDepartmentPerformanceAjax', [DashboardController::class, 'getDepartmentPerformanceAjax'])
            ->name('getDepartmentPerformanceAjax');

        // System Status Routes (Admin only)
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('system-status', [App\Http\Controllers\Admin\SystemStatusController::class, 'index'])->name('system-status.index');
            Route::get('system-status/ajax', [App\Http\Controllers\Admin\SystemStatusController::class, 'getSystemStatus'])->name('system-status.ajax');
            Route::post('system-status/clear-cache', [App\Http\Controllers\Admin\SystemStatusController::class, 'clearCache'])->name('system-status.clear-cache');
            Route::post('system-status/optimize', [App\Http\Controllers\Admin\SystemStatusController::class, 'optimize'])->name('system-status.optimize');
            Route::post('system-status/refresh-menu', [App\Http\Controllers\Admin\SystemStatusController::class, 'refreshMenuCache'])->name('system-status.refresh-menu');
        });

        // Permission Management
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::get('/datatable', [PermissionController::class, 'indexAjax'])->name('datatable');
            Route::post('/', [PermissionController::class, 'store'])->name('store');
            Route::delete('/{id}', [PermissionController::class, 'destroy'])->name('destroy');
            Route::post('/sync-super-admin', [PermissionController::class, 'syncSuperAdmin'])->name('sync-super-admin');
        });

        Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Role Management
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/datatable', [RoleController::class, 'indexAjax'])->name('datatable');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/permissions', [RoleController::class, 'permissions'])->name('permissions');
            Route::put('/{id}/permissions', [RoleController::class, 'updatePermissions'])->name('updatePermissions');
        });

        // Search Routes
        Route::get('/getSearchDataAjax', [BaseController::class, 'getSearchDataAjax'])->name('search.Ajax');

        // Employee routes moved to HRCore module
        // Route::prefix('employees/')->name('employees.')->group(function () {
        //   Route::get('', [EmployeeController::class, 'index'])->name('index');
        //   Route::get('view/{id}', [EmployeeController::class, 'show'])->name('show');
        //   Route::post('indexAjax', [EmployeeController::class, 'userListAjax'])->name('indexAjax');
        //   Route::get('create', [EmployeeController::class, 'create'])->name('create');
        //   Route::get('getNewEmployeeCode/{locationId}', [EmployeeController::class, 'GetNewEmployeeCodeByLocationAjax'])->name('getNewEmployeeCode');
        //   Route::get('checkEmailValidationAjax', [EmployeeController::class, 'checkEmailValidationAjax'])->name('checkEmailValidationAjax');
        //   Route::get('checkPhoneValidationAjax', [EmployeeController::class, 'checkPhoneValidationAjax'])->name('checkPhoneValidationAjax');
        //   Route::get('checkEmployeeCodeValidationAjax', [EmployeeController::class, 'checkEmployeeCodeValidationAjax'])->name('checkEmployeeCodeValidationAjax');
        //   Route::delete('deleteEmployeeAjax/{id}', [EmployeeController::class, 'deleteEmployeeAjax'])->name('deleteEmployeeAjax');
        //   Route::post('store', [EmployeeController::class, 'store'])->name('store');

        //   Route::post('changeEmployeeProfilePicture', [EmployeeController::class, 'changeEmployeeProfilePicture'])->name('changeEmployeeProfilePicture');
        //   Route::post('addHrLocation', [EmployeeController::class, 'addHrLocation'])->name('addHrLocation');
        //   Route::delete('deleteHrLocation/{id}', [EmployeeController::class, 'deleteHrLocation'])->name('deleteHrLocation');
        //   Route::post('addOrUpdateBankAccount', [EmployeeController::class, 'addOrUpdateBankAccount'])->name('addOrUpdateBankAccount');
        //   Route::post('addOrUpdateLeaveCount', [EmployeeController::class, 'addOrUpdateLeaveCount'])->name('addOrUpdateLeaveCount');
        //   Route::post('addOrUpdateDocument', [EmployeeController::class, 'addOrUpdateDocument'])->name('addOrUpdateDocument');
        //   Route::get('getUserDocumentsAjax/{userId}', [EmployeeController::class, 'getUserDocumentsAjax'])->name('getUserDocumentsAjax');
        //   Route::get('downloadUserDocument/{userDocumentId}', [EmployeeController::class, 'downloadUserDocument'])->name('downloadUserDocument');
        //   Route::post('updateBasicInfo', [EmployeeController::class, 'updateBasicInfo'])->name('updateBasicInfo');
        //   Route::post('updateCompensationInfo', [EmployeeController::class, 'updateCompensationInfo'])->name('updateCompensationInfo');
        //   Route::post('addOrUpdateBankAccount', [EmployeeController::class, 'addOrUpdateBankAccount'])->name('addOrUpdateBankAccount');
        //   Route::post('updateWorkInformation', [EmployeeController::class, 'updateWorkInformation'])->name('updateWorkInformation');
        //   Route::post('updateEmergencyContactInfo', [EmployeeController::class, 'updateEmergencyContactInfo'])->name('updateEmergencyContactInfo');

        //   Route::post('addOrUpdatePayrollAdjustment', [EmployeeController::class, 'addOrUpdatePayrollAdjustment'])->name('addOrUpdatePayrollAdjustment');
        //   Route::delete('deletePayrollAdjustment/{id}', [EmployeeController::class, 'deletePayrollAdjustment'])->name('deletePayrollAdjustment');
        //   Route::get('getPayrollAdjustmentAjax/{id}', [EmployeeController::class, 'getPayrollAdjustmentAjax'])->name('getPayrollAdjustmentAjax');

        //   Route::get('getReportingToUsersAjax', [EmployeeController::class, 'getReportingToUsersAjax'])->name('getReportingToUsersAjax');
        //   Route::post('removeDevice', [EmployeeController::class, 'removeDevice'])->name('removeDevice');

        //   //Sales targets
        //   Route::post('addOrUpdateSalesTarget', [EmployeeController::class, 'addOrUpdateSalesTarget'])->name('addOrUpdateSalesTarget');
        //   Route::delete('destroySalesTarget/{id}', [EmployeeController::class, 'destroySalesTarget'])->name('destroySalesTarget');
        //   Route::get('getTargetByIdAjax/{id}', [EmployeeController::class, 'getTargetByIdAjax'])->name('getTargetByIdAjax');

        //   Route::post('toggleStatus/{id}', [EmployeeController::class, 'toggleStatus'])->name('employees.toggleStatus');
        //   Route::post('relieve/{id}', [EmployeeController::class, 'relieveEmployee'])->name('employees.relieve');
        //   Route::post('retire/{id}', [EmployeeController::class, 'retireEmployee'])->name('employees.retire');

        //   Route::post('/{user}/terminate', [EmployeeController::class, 'initiateTermination'])->name('terminate');
        //   Route::post('/{user}/confirmProbation', [EmployeeController::class, 'confirmProbation'])->name('confirmProbation');
        //   Route::post('/{user}/extendProbation', [EmployeeController::class, 'extendProbation'])->name('extendProbation');
        //   Route::post('/{user}/failProbation', [EmployeeController::class, 'failProbation'])->name('failProbation');
        // });

        Route::prefix('account/')->name('account.')->group(function () {
            Route::get('/', [AccountController::class, 'index'])->name('index');
            Route::get('activeInactiveUserAjax/{id}', [AccountController::class, 'activeInactiveUserAjax'])->name('activeInactiveUserAjax');
            Route::get('suspendUserAjax/{id}', [AccountController::class, 'suspendUserAjax'])->name('suspendUserAjax');
            Route::get('deleteUserAjax/{id}', [AccountController::class, 'deleteUserAjax'])->name('deleteUserAjax');
            Route::get('viewUser/{id}', [AccountController::class, 'viewUser'])->name('viewUser');
            Route::get('indexAjax', [AccountController::class, 'userListAjax'])->name('userListAjax');
            Route::delete('deleteUserAjax/{id}', [AccountController::class, 'deleteUserAjax'])->name('deleteUserAjax');
            Route::get('getRolesAjax', [AccountController::class, 'getRolesAjax'])->name('getRolesAjax');
            Route::get('getUsersAjax', [AccountController::class, 'getUsersAjax'])->name('getUsersAjax');
            Route::get('getUsersByRoleAjax/{role}', [AccountController::class, 'getUsersByRoleAjax'])->name('getUsersByRoleAjax');
            Route::post('addOrUpdateUserAjax', [AccountController::class, 'addOrUpdateUserAjax'])->name('addOrUpdateUserAjax');
            Route::get('editUserAjax/{id}', [AccountController::class, 'editUserAjax'])->name('editUserAjax');
            Route::post('updateUserAjax/{id}', [AccountController::class, 'updateUserAjax'])->name('updateUserAjax');
            Route::post('updateUserStatusAjax/{id}', [AccountController::class, 'updateUserStatusAjax'])->name('updateUserStatusAjax');
            Route::post('changeUserStatusAjax/{id}', [AccountController::class, 'changeUserStatusAjax'])->name('changeUserStatusAjax');
            Route::post('changePassword', [AccountController::class, 'changePassword'])->name('changePassword');
        });

        // Settings Routes
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Settings\SettingsController::class, 'index'])->name('index');
            Route::get('/search', [App\Http\Controllers\Settings\SettingsController::class, 'search'])->name('search');
            Route::get('/export', [App\Http\Controllers\Settings\SettingsController::class, 'exportSettings'])->name('export');
            Route::post('/import', [App\Http\Controllers\Settings\SettingsController::class, 'importSettings'])->name('import');

            // System Settings
            Route::prefix('system')->name('system.')->group(function () {
                Route::get('/{category}', [App\Http\Controllers\Settings\SettingsController::class, 'getSystemSettings'])->name('show');
                Route::post('/{category}', [App\Http\Controllers\Settings\SettingsController::class, 'updateSystemSettings'])->name('update');
            });

            // Test Email
            Route::post('/test-email', [App\Http\Controllers\Settings\SettingsController::class, 'testEmailConfiguration'])->name('test-email');

            // Module Settings
            Route::prefix('module')->name('module.')->group(function () {
                Route::get('/{module}', [App\Http\Controllers\Settings\ModuleSettingsController::class, 'index'])->name('index');
                Route::post('/{module}', [App\Http\Controllers\Settings\ModuleSettingsController::class, 'update'])->name('update');
                Route::post('/{module}/reset', [App\Http\Controllers\Settings\ModuleSettingsController::class, 'reset'])->name('reset');
                Route::get('/{module}/form', [App\Http\Controllers\Settings\ModuleSettingsController::class, 'getModuleForm'])->name('form');
            });

            // Settings History
            Route::prefix('history')->name('history.')->group(function () {
                Route::get('/', [App\Http\Controllers\Settings\SettingsHistoryController::class, 'index'])->name('index');
                Route::get('/export', [App\Http\Controllers\Settings\SettingsHistoryController::class, 'export'])->name('export');
                Route::get('/{key}', [App\Http\Controllers\Settings\SettingsHistoryController::class, 'getSettingHistory'])->name('show');
                Route::post('/{historyId}/rollback', [App\Http\Controllers\Settings\SettingsHistoryController::class, 'rollback'])->name('rollback');
            });
        });

        // Employees - moved to HRCore module
        // Route::get('employee/getGeofenceGroups', [EmployeeController::class, 'getGeofenceGroups'])->name('employee.getGeofenceGroups');
        // Route::get('employee/getIpGroups', [EmployeeController::class, 'getIpGroups'])->name('employee.getIpGroups');
        // Route::get('employee/getQrGroups', [EmployeeController::class, 'getQrGroups'])->name('employee.getQrGroups');
        // Route::get('employee/getSites', [EmployeeController::class, 'getSites'])->name('employee.getSites');
        // Route::get('employee/getDynamicQrDevices', [EmployeeController::class, 'getDynamicQrDevices'])->name('employee.getDynamicQrDevices');

        // Route::get('employee/myProfile', [EmployeeController::class, 'myProfile'])->name('employee.myProfile');

        // User Profile Routes
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::post('/update-basic-info', [ProfileController::class, 'updateBasicInfo'])->name('update-basic-info');
            Route::post('/update-picture', [ProfileController::class, 'updateProfilePicture'])->name('update-picture');
            Route::delete('/remove-picture', [ProfileController::class, 'removeProfilePicture'])->name('remove-picture');
            Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
            Route::post('/update-notifications', [ProfileController::class, 'updateNotificationPreferences'])->name('update-notifications');
            Route::get('/sessions', [ProfileController::class, 'getSessions'])->name('sessions');
            Route::post('/terminate-session', [ProfileController::class, 'terminateSession'])->name('terminate-session');
            Route::post('/terminate-all-sessions', [ProfileController::class, 'terminateAllSessions'])->name('terminate-all-sessions');
        });
    });

    // Device Status
    Route::group(['prefix' => 'device'], function () {
        Route::get('/', [DeviceController::class, 'index'])->name('device.index');
        Route::get('/indexAjax', [DeviceController::class, 'indexAjax'])->name('device.indexAjax');
        Route::get('/getByIdAjax/{id}', [DeviceController::class, 'getByIdAjax'])->name('device.getByIdAjax');
        Route::delete('/deleteAjax/{id}', [DeviceController::class, 'deleteAjax'])->name('device.deleteAjax');
    });

});
