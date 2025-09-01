<?php

use Illuminate\Support\Facades\Route;
use Modules\CRMCore\app\Http\Controllers\CompanyController;
use Modules\CRMCore\app\Http\Controllers\ContactController;
use Modules\CRMCore\app\Http\Controllers\CustomerController;
use Modules\CRMCore\app\Http\Controllers\CustomerGroupController;
use Modules\CRMCore\app\Http\Controllers\DashboardController;
use Modules\CRMCore\app\Http\Controllers\DealController;
use Modules\CRMCore\app\Http\Controllers\DealPipelineController;
use Modules\CRMCore\app\Http\Controllers\DealStageController;
use Modules\CRMCore\app\Http\Controllers\LeadController;
use Modules\CRMCore\app\Http\Controllers\LeadSourceController;
use Modules\CRMCore\app\Http\Controllers\LeadStatusController;
use Modules\CRMCore\app\Http\Controllers\TaskController;
use Modules\CRMCore\app\Http\Controllers\TaskPriorityController;
use Modules\CRMCore\app\Http\Controllers\TaskStatusController;
use Modules\CRMCore\app\Http\Controllers\CRMCoreSettingsController;

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

Route::middleware(['auth'])->group(function () {

    // CRM Dashboard routes
    Route::prefix('crm')->name('crm.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard/deals-chart', [DashboardController::class, 'getDealsChartData'])->name('dashboard.deals-chart');
        Route::get('/dashboard/leads-chart', [DashboardController::class, 'getLeadsChartData'])->name('dashboard.leads-chart');
        Route::get('/dashboard/tasks-chart', [DashboardController::class, 'getTasksChartData'])->name('dashboard.tasks-chart');
    });

    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('index');
        Route::get('/ajax', [CompanyController::class, 'getDataAjax'])->name('ajax'); // For DataTables

        Route::get('/create', [CompanyController::class, 'create'])->name('create');
        Route::post('/', [CompanyController::class, 'store'])->name('store');

        Route::get('/{company}', [CompanyController::class, 'show'])->name('show'); // Route model binding
        Route::get('/{company}/deals-ajax', [CompanyController::class, 'getCompanyDealsAjax'])->name('dealsAjax'); // AJAX endpoint for deals refresh
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit'); // Route model binding
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update'); // Route model binding

        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('destroy'); // Route model binding

        // Optional: For quick status toggle from list view if needed
        Route::post('/{company}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('toggleStatus');

    });
    Route::get('companiess/select-search', [CompanyController::class, 'selectSearch'])->name('companies.selectSearch');

    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::post('/ajax', [ContactController::class, 'getDataAjax'])->name('ajax');

        Route::get('/create', [ContactController::class, 'create'])->name('create');
        Route::post('/', [ContactController::class, 'store'])->name('store');

        Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
        Route::get('/{contact}/edit', [ContactController::class, 'edit'])->name('edit');
        Route::put('/{contact}', [ContactController::class, 'update'])->name('update');

        Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');

        Route::post('/{contact}/toggle-status', [ContactController::class, 'toggleStatus'])->name('toggleStatus');

    });

    Route::get('contactss/{contact}/details-ajax', [ContactController::class, 'getDetailsAjax'])->name('contacts.detailsAjax');
    Route::get('contactss/select-search', [ContactController::class, 'selectSearch'])->name('contacts.selectSearch');

    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/datatable', [CustomerController::class, 'datatable'])->name('datatable');
        Route::get('/statistics', [CustomerController::class, 'statistics'])->name('statistics');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::post('/{customer}/blacklist', [CustomerController::class, 'toggleBlacklist'])->name('blacklist');
        Route::get('/search/contacts', [CustomerController::class, 'searchContacts'])->name('search.contacts');
    });
    
    // Customer Group Management
    Route::prefix('customer-groups')->name('customer-groups.')->group(function () {
        Route::get('/', [CustomerGroupController::class, 'index'])->name('index');
        Route::get('/datatable', [CustomerGroupController::class, 'datatable'])->name('datatable');
        Route::get('/statistics', [CustomerGroupController::class, 'statistics'])->name('statistics');
        Route::post('/', [CustomerGroupController::class, 'store'])->name('store');
        Route::get('/{id}', [CustomerGroupController::class, 'show'])->name('show');
        Route::put('/{id}', [CustomerGroupController::class, 'update'])->name('update');
        Route::delete('/{id}', [CustomerGroupController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('leads')->name('leads.')->group(function () {
        // Main view for List/Kanban
        Route::get('/', [LeadController::class, 'index'])->name('index');

        // --- AJAX Routes ---
        Route::post('/datatable-ajax', [LeadController::class, 'getDataTableAjax'])->name('dataTableAjax');
        Route::get('/kanban-ajax', [LeadController::class, 'getKanbanDataAjax'])->name('kanbanAjax');

        // For getting data to populate the offcanvas edit form
        Route::get('/{lead}/ajax', [LeadController::class, 'getLeadAjax'])->name('getLeadAjax');

        // CRUD actions from the offcanvas form
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
        Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');

        // For handling Kanban drag-and-drop
        Route::post('/{lead}/update-stage', [LeadController::class, 'updateKanbanStage'])->name('updateKanbanStage');

        // --- Full Page View ---
        // A dedicated "show" page is still useful for viewing all details
        Route::get('/{lead}', [LeadController::class, 'show'])->name('show');

        Route::post('/{lead}/convert', [LeadController::class, 'processConversion'])->name('processConversion');
    });

    Route::get('leadss/select-search', [LeadController::class, 'selectSearch'])->name('leads.selectSearch');

    Route::prefix('deals')->name('deals.')->group(function () {
        // Main view for Kanban/List
        Route::get('/', [DealController::class, 'index'])->name('index');

        // --- AJAX Routes ---
        Route::get('/kanban-ajax', [DealController::class, 'getKanbanDataAjax'])->name('kanbanAjax');
        Route::post('/datatable-ajax', [DealController::class, 'getDataTableAjax'])->name('dataTableAjax'); // If list view is added

        // For getting data to populate the offcanvas edit form
        Route::get('/{deal}/ajax', [DealController::class, 'getDealAjax'])->name('getDealAjax');

        // CRUD actions from the offcanvas form
        Route::post('/', [DealController::class, 'store'])->name('store');
        Route::put('/{deal}', [DealController::class, 'update'])->name('update');
        Route::delete('/{deal}', [DealController::class, 'destroy'])->name('destroy');

        // For handling Kanban drag-and-drop
        Route::post('/{deal}/update-stage', [DealController::class, 'updateKanbanStage'])->name('updateKanbanStage');

        // --- Full Page View ---
        Route::get('/{deal}', [DealController::class, 'show'])->name('show');
    });

    Route::get('dealss/select-search', [DealController::class, 'selectSearch'])->name('deals.selectSearch');

    Route::prefix('tasks')->name('tasks.')->group(function () {
        // Main view for listing tasks
        Route::get('/', [TaskController::class, 'index'])->name('index');

        // --- AJAX Routes ---
        Route::post('/datatable-ajax', [TaskController::class, 'getDataTableAjax'])->name('dataTableAjax');
        Route::get('/kanban-ajax', [TaskController::class, 'getKanbanAjax'])->name('kanbanAjax');

        // For getting data to populate the offcanvas edit form
        Route::get('/{task}/ajax', [TaskController::class, 'getTaskAjax'])->name('getTaskAjax');

        // CRUD actions from the offcanvas form
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::put('/{task}', [TaskController::class, 'update'])->name('update'); // Handles full updates and quick status changes
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');

        // For handling Kanban drag-and-drop
        Route::post('/{task}/update-kanban-status', [TaskController::class, 'updateKanbanStatus'])->name('updateKanbanStatus');

        // Optional: A dedicated route if you want a full-page view of a task, though less critical with offcanvas.
        // Route::get('/{task}', [TaskController::class, 'show'])->name('show');
    });

    Route::prefix('settings')->name('settings.')->group(function () { // Common prefix for all settings
        Route::prefix('lead-statuses')->name('leadStatuses.')->group(function () {
            Route::get('/', [LeadStatusController::class, 'index'])->name('index');
            Route::post('/ajax', [LeadStatusController::class, 'getDataAjax'])->name('ajax'); // For DataTables or a dynamic list

            // For getting data to populate the offcanvas edit form
            Route::get('/{lead_status}/ajax', [LeadStatusController::class, 'getLeadStatusAjax'])->name('getLeadStatusAjax');

            Route::post('/', [LeadStatusController::class, 'store'])->name('store'); // AJAX from offcanvas
            Route::put('/{lead_status}', [LeadStatusController::class, 'update'])->name('update'); // AJAX from offcanvas
            Route::delete('/{lead_status}', [LeadStatusController::class, 'destroy'])->name('destroy'); // AJAX

            Route::post('/update-order', [LeadStatusController::class, 'updateOrder'])->name('updateOrder'); // For drag-and-drop reordering
        });

        Route::prefix('lead-sources')->name('leadSources.')->group(function () {
            Route::get('/', [LeadSourceController::class, 'index'])->name('index');
            Route::post('/ajax', [LeadSourceController::class, 'getDataAjax'])->name('ajax'); // For DataTables

            Route::get('/{lead_source}/ajax', [LeadSourceController::class, 'getLeadSourceAjax'])->name('getLeadSourceAjax');

            Route::post('/', [LeadSourceController::class, 'store'])->name('store');
            Route::put('/{lead_source}', [LeadSourceController::class, 'update'])->name('update');
            Route::delete('/{lead_source}', [LeadSourceController::class, 'destroy'])->name('destroy');
            Route::post('/{lead_source}/toggle-status', [LeadSourceController::class, 'toggleStatus'])->name('toggleStatus');
        });

        // Standalone deal stages route for viewing all stages across all pipelines
        // This redirects to the first/default pipeline's stages
        Route::prefix('deal-stages')->name('dealStages.')->group(function () {
            Route::get('/', [DealStageController::class, 'allStages'])->name('index');
            Route::post('/datatable', [DealStageController::class, 'datatable'])->name('datatable');
        });

        Route::prefix('deal-pipelines/{deal_pipeline}/stages')->name('dealPipelineStages.')->group(function () {
            // Note: {deal_pipeline} will be the DealPipeline model instance due to route model binding
            Route::get('/', [DealStageController::class, 'index'])->name('index');
            // Route::post('/ajax', [DealStageController::class, 'getDataAjax'])->name('ajax'); // If using DataTables

            Route::post('/', [DealStageController::class, 'store'])->name('store'); // AJAX from offcanvas
            Route::get('/{deal_stage}/ajax', [DealStageController::class, 'getStageAjax'])->name('getStageAjax'); // For edit form
            Route::put('/{deal_stage}', [DealStageController::class, 'update'])->name('update'); // AJAX from offcanvas
            Route::delete('/{deal_stage}', [DealStageController::class, 'destroy'])->name('destroy'); // AJAX

            Route::post('/update-order', [DealStageController::class, 'updateOrder'])->name('updateOrder'); // For drag-and-drop
        });

        Route::prefix('deal-pipelines')->name('dealPipelines.')->group(function () {
            Route::get('/', [DealPipelineController::class, 'index'])->name('index');
            Route::get('/datatable', [DealPipelineController::class, 'datatable'])->name('datatable');

            Route::post('/', [DealPipelineController::class, 'store'])->name('store');
            Route::get('/{deal_pipeline}/ajax', [DealPipelineController::class, 'getPipelineAjax'])->name('getPipelineAjax');
            Route::put('/{deal_pipeline}', [DealPipelineController::class, 'update'])->name('update');
            Route::delete('/{deal_pipeline}', [DealPipelineController::class, 'destroy'])->name('destroy');
            Route::post('/{deal_pipeline}/toggle-status', [DealPipelineController::class, 'toggleStatus'])->name('toggleStatus');
            Route::post('/update-order', [DealPipelineController::class, 'updateOrder'])->name('updateOrder');
        });

        Route::prefix('task-statuses')->name('taskStatuses.')->group(function () {
            Route::get('/', [TaskStatusController::class, 'index'])->name('index');
            // Route::post('/ajax', [TaskStatusController::class, 'getDataAjax'])->name('ajax'); // Optional for DataTables

            Route::post('/', [TaskStatusController::class, 'store'])->name('store'); // AJAX from offcanvas
            Route::get('/{task_status}/ajax', [TaskStatusController::class, 'getTaskStatusAjax'])->name('getTaskStatusAjax'); // For edit form
            Route::put('/{task_status}', [TaskStatusController::class, 'update'])->name('update'); // AJAX from offcanvas
            Route::delete('/{task_status}', [TaskStatusController::class, 'destroy'])->name('destroy'); // AJAX
            Route::post('/update-order', [TaskStatusController::class, 'updateOrder'])->name('updateOrder'); // For drag-and-drop
        });

        Route::prefix('task-priorities')->name('taskPriorities.')->group(function () {
            Route::get('/', [TaskPriorityController::class, 'index'])->name('index');
            Route::post('/ajax', [TaskPriorityController::class, 'getDataAjax'])->name('ajax'); // For DataTables

            Route::post('/', [TaskPriorityController::class, 'store'])->name('store'); // AJAX from offcanvas
            Route::post('/update-order', [TaskPriorityController::class, 'updateOrder'])->name('updateOrder'); // For drag-and-drop
            Route::get('/{task_priority}/ajax', [TaskPriorityController::class, 'getPriorityAjax'])->name('getPriorityAjax'); // For edit form
            Route::put('/{task_priority}', [TaskPriorityController::class, 'update'])->name('update'); // AJAX from offcanvas
            Route::delete('/{task_priority}', [TaskPriorityController::class, 'destroy'])->name('destroy'); // AJAX
        });

        // CRM Settings routes
        Route::prefix('crm-settings')->name('crm-settings.')->group(function () {
            Route::get('/', [CRMCoreSettingsController::class, 'index'])->name('index');
            Route::get('/show', [CRMCoreSettingsController::class, 'show'])->name('show');
            Route::post('/update', [CRMCoreSettingsController::class, 'update'])->name('update');
            Route::post('/reset', [CRMCoreSettingsController::class, 'reset'])->name('reset');
            Route::get('/export', [CRMCoreSettingsController::class, 'export'])->name('export');
            Route::post('/import', [CRMCoreSettingsController::class, 'import'])->name('import');
            Route::post('/update-single', [CRMCoreSettingsController::class, 'updateSingleSetting'])->name('update-single');
            Route::get('/get-settings', [CRMCoreSettingsController::class, 'getSettings'])->name('get-settings');
            Route::post('/get-setting', [CRMCoreSettingsController::class, 'getSetting'])->name('get-setting');
        });
    });

});
