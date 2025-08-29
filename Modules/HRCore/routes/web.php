<?php

use Illuminate\Support\Facades\Route;
use Modules\HRCore\app\Http\Controllers\AttendanceController;
use Modules\HRCore\app\Http\Controllers\AttendanceDashboardController;
use Modules\HRCore\app\Http\Controllers\DepartmentsController;
use Modules\HRCore\app\Http\Controllers\DesignationController;
use Modules\HRCore\app\Http\Controllers\EmployeeController;
use Modules\HRCore\app\Http\Controllers\ExpenseController;
use Modules\HRCore\app\Http\Controllers\ExpenseTypeController;
use Modules\HRCore\app\Http\Controllers\HolidayController;
use Modules\HRCore\app\Http\Controllers\LeaveController;
use Modules\HRCore\app\Http\Controllers\CompensatoryOffController;
use Modules\HRCore\app\Http\Controllers\LeaveTypeController;
use Modules\HRCore\app\Http\Controllers\OrganisationHierarchyController;
use Modules\HRCore\app\Http\Controllers\ReportController;
use Modules\HRCore\app\Http\Controllers\ShiftController;
use Modules\HRCore\app\Http\Controllers\TeamController;

/*
|--------------------------------------------------------------------------
| HRCore Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the HRCore module.
| All routes are prefixed with 'hrcore' and named with 'hrcore.' prefix
| for consistency and to avoid conflicts with other modules.
|
*/

Route::prefix('hrcore')->name('hrcore.')->middleware(['auth', 'web'])->group(function () {
    
    // Dashboard
    Route::get('/', function() {
        return redirect()->route('hrcore.attendance.index');
    })->name('dashboard');
    Route::get('/dashboard', function() {
        return redirect()->route('hrcore.attendance.index');
    })->name('dashboard.index');
    
    // Employee Management
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/datatable', [EmployeeController::class, 'indexAjax'])->name('datatable');
        Route::get('/search', [EmployeeController::class, 'search'])->name('search');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/my-profile', [EmployeeController::class, 'myProfile'])->name('my-profile');
        Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/update-status', [EmployeeController::class, 'updateStatus'])->name('update-status');
        Route::post('/{id}/lifecycle/change', [EmployeeController::class, 'changeLifecycleState'])->name('lifecycle.change');
    });
    
    // Employee Self-Service Routes (No admin permissions required)
    Route::prefix('self-service')->name('self-service.')->group(function () {
        Route::get('/profile', [EmployeeController::class, 'selfServiceProfile'])->name('profile');
        Route::post('/profile/update', [EmployeeController::class, 'updateSelfProfile'])->name('profile.update');
        Route::post('/profile/photo', [EmployeeController::class, 'updateProfilePhoto'])->name('profile.photo');
        Route::post('/profile/password', [EmployeeController::class, 'changePassword'])->name('profile.password');
    });
    
    // Attendance Management
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/datatable', [AttendanceController::class, 'indexAjax'])->name('datatable');
        Route::get('/web-attendance', [AttendanceController::class, 'webAttendance'])->name('web-attendance');
        Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])->name('my-attendance');
        Route::get('/regularization', [AttendanceController::class, 'regularization'])->name('regularization');
        Route::get('/reports', [AttendanceController::class, 'myReports'])->name('reports');
        Route::get('/today-status', [AttendanceController::class, 'getTodayStatus'])->name('today-status');
        Route::get('/global-status', [AttendanceController::class, 'getGlobalStatus'])->name('global-status');
        Route::post('/web-check-in', [AttendanceController::class, 'webCheckIn'])->name('web-check-in');
        Route::post('/web-check-out', [AttendanceController::class, 'webCheckOut'])->name('web-check-out');
        Route::get('/export', [AttendanceController::class, 'export'])->name('export');
        Route::get('/statistics', [AttendanceController::class, 'statistics'])->name('statistics');
        Route::get('/{id}/details', [AttendanceController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [AttendanceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AttendanceController::class, 'update'])->name('update');
    });
    
    // Attendance Regularization
    Route::prefix('attendance-regularization')->name('attendance-regularization.')->group(function () {
        Route::get('/', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'index'])->name('index');
        Route::get('/datatable', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'create'])->name('create');
        Route::post('/', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'edit'])->name('edit');
        Route::put('/{id}', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [Modules\HRCore\app\Http\Controllers\AttendanceRegularizationController::class, 'reject'])->name('reject');
    });
    
    // Attendance Dashboard
    Route::prefix('attendance-dashboard')->name('attendance-dashboard.')->group(function () {
        Route::get('/', [AttendanceDashboardController::class, 'index'])->name('index');
        Route::get('/stats', [AttendanceDashboardController::class, 'getStats'])->name('stats');
        Route::get('/team-attendance', [AttendanceDashboardController::class, 'getTeamAttendance'])->name('team-attendance');
        Route::get('/pending-regularizations', [AttendanceDashboardController::class, 'getPendingRegularizations'])->name('pending-regularizations');
        Route::get('/attendance-summary', [AttendanceDashboardController::class, 'getAttendanceSummary'])->name('attendance-summary');
    });
    
    // Leave Management (Legacy - kept for backward compatibility)
    Route::prefix('leaves')->name('leaves.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])->name('index');
        Route::get('/datatable', [LeaveController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [LeaveController::class, 'create'])->name('create');
        Route::get('/apply', [LeaveController::class, 'applyLeave'])->name('apply');
        Route::get('/balance', [LeaveController::class, 'myBalance'])->name('balance');
        Route::get('/balance/{leaveTypeId}', [LeaveController::class, 'getLeaveBalanceForType'])->name('balance.type');
        Route::get('/team', [LeaveController::class, 'teamCalendar'])->name('team');
        Route::post('/', [LeaveController::class, 'store'])->name('store');
        Route::get('/{id}', [LeaveController::class, 'showPage'])->name('show');
        Route::get('/{id}/edit', [LeaveController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LeaveController::class, 'update'])->name('update');
        Route::delete('/{id}', [LeaveController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/action', [LeaveController::class, 'actionAjax'])->name('action');
        Route::post('/{id}/approve', [LeaveController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [LeaveController::class, 'reject'])->name('reject');
        Route::post('/{id}/cancel', [LeaveController::class, 'cancel'])->name('cancel');
    });
    
    
    // Compensatory Offs
    Route::prefix('compensatory-offs')->name('compensatory-offs.')->group(function () {
        Route::get('/', [CompensatoryOffController::class, 'index'])->name('index');
        Route::get('/datatable', [CompensatoryOffController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [CompensatoryOffController::class, 'create'])->name('create');
        Route::post('/', [CompensatoryOffController::class, 'store'])->name('store');
        Route::get('/statistics', [CompensatoryOffController::class, 'statistics'])->name('statistics');
        Route::get('/{id}', [CompensatoryOffController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CompensatoryOffController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CompensatoryOffController::class, 'update'])->name('update');
        Route::delete('/{id}', [CompensatoryOffController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [CompensatoryOffController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [CompensatoryOffController::class, 'reject'])->name('reject');
    });
    
    // Leave Types
    Route::prefix('leave-types')->name('leave-types.')->group(function () {
        Route::get('/', [LeaveTypeController::class, 'index'])->name('index');
        Route::get('/datatable', [LeaveTypeController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [LeaveTypeController::class, 'create'])->name('create');
        Route::post('/', [LeaveTypeController::class, 'store'])->name('store');
        Route::get('/{id}', [LeaveTypeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [LeaveTypeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LeaveTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [LeaveTypeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [LeaveTypeController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/check-code', [LeaveTypeController::class, 'checkCodeValidationAjax'])->name('check-code');
    });
    
    // Leave Balance Management
    Route::prefix('leave-balance')->name('leave-balance.')->group(function () {
        Route::get('/', [Modules\HRCore\app\Http\Controllers\LeaveBalanceController::class, 'index'])->name('index');
        Route::get('/datatable', [Modules\HRCore\app\Http\Controllers\LeaveBalanceController::class, 'indexAjax'])->name('datatable');
        Route::get('/summary', [Modules\HRCore\app\Http\Controllers\LeaveBalanceController::class, 'getBalanceSummary'])->name('summary');
        Route::get('/{employeeId}', [Modules\HRCore\app\Http\Controllers\LeaveBalanceController::class, 'show'])->name('show');
        Route::post('/set-initial', [Modules\HRCore\app\Http\Controllers\LeaveBalanceController::class, 'setInitialBalance'])->name('set-initial');
        Route::post('/adjust', [Modules\HRCore\app\Http\Controllers\LeaveBalanceController::class, 'adjustBalance'])->name('adjust');
        Route::post('/bulk-set', [Modules\HRCore\app\Http\Controllers\LeaveBalanceController::class, 'bulkSetInitialBalance'])->name('bulk-set');
    });
    
    // Shifts
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])->name('index');
        Route::get('/datatable', [ShiftController::class, 'listAjax'])->name('datatable');
        Route::get('/create', [ShiftController::class, 'create'])->name('create');
        Route::post('/', [ShiftController::class, 'store'])->name('store');
        Route::get('/{id}', [ShiftController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ShiftController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ShiftController::class, 'update'])->name('update');
        Route::delete('/{id}', [ShiftController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [ShiftController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/active-list', [ShiftController::class, 'getActiveShiftsForDropdown'])->name('active-list');
    });
    
    // Departments
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [DepartmentsController::class, 'index'])->name('index');
        Route::get('/datatable', [DepartmentsController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [DepartmentsController::class, 'create'])->name('create');
        Route::get('/parent-list', [DepartmentsController::class, 'getParentDepartments'])->name('parent-list');
        Route::get('/list', [DepartmentsController::class, 'getListAjax'])->name('list');
        Route::post('/', [DepartmentsController::class, 'store'])->name('store');
        Route::get('/{id}', [DepartmentsController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DepartmentsController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DepartmentsController::class, 'update'])->name('update');
        Route::delete('/{id}', [DepartmentsController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [DepartmentsController::class, 'toggleStatus'])->name('toggle-status');
        
        // Legacy routes for backward compatibility
        Route::post('/addOrUpdateDepartmentAjax', [DepartmentsController::class, 'addOrUpdateDepartmentAjax'])->name('addOrUpdateDepartmentAjax');
        Route::get('/getDepartmentAjax/{id}', [DepartmentsController::class, 'getDepartmentAjax'])->name('getDepartmentAjax');
        Route::delete('/deleteAjax/{id}', [DepartmentsController::class, 'deleteAjax'])->name('deleteAjax');
        Route::post('/changeStatus/{id}', [DepartmentsController::class, 'changeStatus'])->name('changeStatus');
        Route::get('/getParentDepartments', [DepartmentsController::class, 'getParentDepartments'])->name('getParentDepartments');
        Route::get('/indexAjax', [DepartmentsController::class, 'indexAjax'])->name('indexAjax');
    });
    
    // Designations
    Route::prefix('designations')->name('designations.')->group(function () {
        Route::get('/', [DesignationController::class, 'index'])->name('index');
        Route::get('/datatable', [DesignationController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [DesignationController::class, 'create'])->name('create');
        Route::get('/list', [DesignationController::class, 'getDesignationListAjax'])->name('list');
        Route::get('/check-code', [DesignationController::class, 'checkCodeValidationAjax'])->name('check-code');
        Route::post('/', [DesignationController::class, 'store'])->name('store');
        Route::get('/{id}', [DesignationController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DesignationController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DesignationController::class, 'update'])->name('update');
        Route::delete('/{id}', [DesignationController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [DesignationController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Teams
    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/datatable', [TeamController::class, 'getTeamsListAjax'])->name('datatable');
        Route::get('/create', [TeamController::class, 'create'])->name('create');
        Route::get('/list', [TeamController::class, 'getTeamListAjax'])->name('list');
        Route::get('/check-code', [TeamController::class, 'checkCodeValidationAjax'])->name('check-code');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TeamController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{id}', [TeamController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [TeamController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Expense Types
    Route::prefix('expense-types')->name('expense-types.')->group(function () {
        Route::get('/', [ExpenseTypeController::class, 'index'])->name('index');
        Route::get('/datatable', [ExpenseTypeController::class, 'indexAjax'])->name('datatable');
        Route::post('/', [ExpenseTypeController::class, 'store'])->name('store');
        Route::get('/{id}', [ExpenseTypeController::class, 'show'])->name('show');
        Route::put('/{id}', [ExpenseTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExpenseTypeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [ExpenseTypeController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/check-code', [ExpenseTypeController::class, 'checkCodeValidationAjax'])->name('check-code');
    });
    
    // Expense Requests
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/datatable', [ExpenseController::class, 'indexAjax'])->name('datatable');
        Route::get('/my-expenses', [ExpenseController::class, 'myExpenses'])->name('my-expenses');
        Route::get('/my-expenses/datatable', [ExpenseController::class, 'myExpensesAjax'])->name('my-expenses.datatable');
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::get('/{id}', [ExpenseController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ExpenseController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ExpenseController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExpenseController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [ExpenseController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [ExpenseController::class, 'reject'])->name('reject');
        Route::post('/{id}/process', [ExpenseController::class, 'process'])->name('process');
    });
    
    // Holidays
    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::get('/my-holidays', [HolidayController::class, 'myHolidays'])->name('my-holidays');
        Route::get('/datatable', [HolidayController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [HolidayController::class, 'create'])->name('create');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::get('/{id}', [HolidayController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [HolidayController::class, 'edit'])->name('edit');
        Route::put('/{id}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{id}', [HolidayController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [HolidayController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Organization Hierarchy
    Route::prefix('organization-hierarchy')->name('organization-hierarchy.')->group(function () {
        Route::get('/', [OrganisationHierarchyController::class, 'index'])->name('index');
        Route::get('/data', [OrganisationHierarchyController::class, 'getData'])->name('data');
    });
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/attendance', [ReportController::class, 'getAttendanceReport'])->name('attendance');
        Route::post('/leaves', [ReportController::class, 'getLeaveReport'])->name('leaves');
        Route::post('/expenses', [ReportController::class, 'getExpenseReport'])->name('expenses');
        Route::post('/visits', [ReportController::class, 'getVisitReport'])->name('visits');
        Route::post('/product-orders', [ReportController::class, 'getProductOrderReport'])->name('product-orders');
    });
    
    // API/AJAX endpoints that need consistent naming
    Route::prefix('ajax')->name('ajax.')->group(function () {
        // Common AJAX operations
        Route::post('/store-update', function() {
            // This is a placeholder for handling all AJAX store/update operations
            // Each controller will implement its own logic
        })->name('store-update');
    });
});

// Legacy route redirects for backward compatibility
Route::group(['middleware' => ['auth', 'web']], function () {
    // Redirect old routes to new standardized routes
    Route::get('employees', function() {
        return redirect()->route('hrcore.employees.index');
    });
    Route::get('attendance', function() {
        return redirect()->route('hrcore.attendance.index');
    });
    Route::get('leaves', function() {
        return redirect()->route('hrcore.leaves.index');
    });
    Route::get('shifts', function() {
        return redirect()->route('hrcore.shifts.index');
    });
    Route::get('expenses', function() {
        return redirect()->route('hrcore.expenses.index');
    });
    Route::get('departments', function() {
        return redirect()->route('hrcore.departments.index');
    });
    Route::get('designations', function() {
        return redirect()->route('hrcore.designations.index');
    });
    Route::get('teams', function() {
        return redirect()->route('hrcore.teams.index');
    });
    Route::get('holidays', function() {
        return redirect()->route('hrcore.holidays.index');
    });
});