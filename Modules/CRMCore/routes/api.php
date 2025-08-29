<?php

use Illuminate\Support\Facades\Route;
use Modules\CRMCore\app\Http\Controllers\Api\V1\LeadController;
use Modules\CRMCore\app\Http\Controllers\Api\V1\DealController;
use Modules\CRMCore\app\Http\Controllers\Api\V1\TaskController;
use Modules\CRMCore\app\Http\Controllers\Api\V1\ContactController;
use Modules\CRMCore\app\Http\Controllers\Api\V1\CompanyController;
use Modules\CRMCore\app\Http\Controllers\Api\V1\CustomerController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * CRM Core Mobile App API Routes
 *
 */

Route::middleware('api')->prefix('crmcore')->group(function () {

  Route::group(['prefix' => 'v1', 'middleware' => 'auth:api'], function () {

    // Lead endpoints
    Route::prefix('leads')->group(function () {
      Route::get('/', [LeadController::class, 'index']);
      Route::post('/', [LeadController::class, 'store']);
      Route::get('/statistics', [LeadController::class, 'statistics']);
      Route::get('/statuses', [LeadController::class, 'getStatuses']);
      Route::get('/sources', [LeadController::class, 'getSources']);
      Route::post('/bulk-update-status', [LeadController::class, 'bulkUpdateStatus']);
      Route::get('/{id}', [LeadController::class, 'show']);
      Route::put('/{id}', [LeadController::class, 'update']);
      Route::delete('/{id}', [LeadController::class, 'destroy']);
      Route::post('/{id}/convert-to-deal', [LeadController::class, 'convertToDeal']);
    });

    // Deal endpoints
    Route::prefix('deals')->group(function () {
      Route::get('/', [DealController::class, 'index']);
      Route::post('/', [DealController::class, 'store']);
      Route::get('/statistics', [DealController::class, 'statistics']);
      Route::get('/pipelines', [DealController::class, 'getPipelines']);
      Route::get('/{id}', [DealController::class, 'show']);
      Route::put('/{id}', [DealController::class, 'update']);
      Route::delete('/{id}', [DealController::class, 'destroy']);
      Route::post('/{id}/mark-as-won', [DealController::class, 'markAsWon']);
      Route::post('/{id}/mark-as-lost', [DealController::class, 'markAsLost']);
      Route::post('/{id}/move-stage', [DealController::class, 'moveStage']);
    });

    // Contact endpoints
    Route::prefix('contacts')->group(function () {
      Route::get('/', [ContactController::class, 'index']);
      Route::post('/', [ContactController::class, 'store']);
      Route::get('/search', [ContactController::class, 'search']);
      Route::get('/{id}', [ContactController::class, 'show']);
      Route::put('/{id}', [ContactController::class, 'update']);
      Route::delete('/{id}', [ContactController::class, 'destroy']);
      Route::post('/{id}/set-primary', [ContactController::class, 'setPrimary']);
    });

    // Company endpoints
    Route::prefix('companies')->group(function () {
      Route::get('/', [CompanyController::class, 'index']);
      Route::post('/', [CompanyController::class, 'store']);
      Route::get('/search', [CompanyController::class, 'search']);
      Route::get('/statistics', [CompanyController::class, 'statistics']);
      Route::get('/{id}', [CompanyController::class, 'show']);
      Route::put('/{id}', [CompanyController::class, 'update']);
      Route::delete('/{id}', [CompanyController::class, 'destroy']);
      Route::get('/{id}/contacts', [CompanyController::class, 'getContacts']);
      Route::post('/{id}/add-contact', [CompanyController::class, 'addContact']);
    });

    // Customer endpoints
    Route::prefix('customers')->group(function () {
      Route::get('/', [CustomerController::class, 'index']);
      Route::post('/', [CustomerController::class, 'store']);
      Route::get('/statistics', [CustomerController::class, 'statistics']);
      Route::get('/search', [CustomerController::class, 'search']);
      Route::get('/groups', [CustomerController::class, 'getGroups']);
      Route::get('/{id}', [CustomerController::class, 'show']);
      Route::put('/{id}', [CustomerController::class, 'update']);
      Route::delete('/{id}', [CustomerController::class, 'destroy']);
      Route::post('/bulk-update-status', [CustomerController::class, 'bulkUpdateStatus']);
    });
  });
});


