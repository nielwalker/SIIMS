<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardControllerV2;
use Illuminate\Support\Facades\Route;

/**
 * Dashboard Routes
 */
Route::prefix('/dashboards')->middleware('role:admin,dean,chairperson,coordinator,osa,company,supervisor')->group(function () {

  /**
   * GET
   */

  // Version 2
  Route::get('/v2', [DashboardControllerV2::class, 'getDashboard']);


  // Version 1
  Route::get('/', [DashboardController::class, 'get']);
});
