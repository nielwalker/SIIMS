<?php

// User Routes
// resources: /users

use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\OsaController;
use App\Http\Controllers\StudentReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('/users')->group(function () {


  // SOFT DELETE SELECTED USERS
  Route::post('/archive/selected', [UserController::class, 'archiveUsers']);

  /**
   * Route name: Dean Routes
   * API Route: Dean API Portal
   * Roles Allowed: TBA
   */
  require base_path('routes/deans.php');


  /**
   * Route name: Student Routes
   * API Route: Student API Portal
   * Roles Allowed: All
   */
  require base_path('routes/students.php');

  /**
   * Route name: Supervisor Routes
   * API Route: Supervisor API Portal
   * Roles Allowed: TBA
   */
  require base_path('routes/supervisors.php');

  /**
   * Route name: Chairperson Routes
   * API Route: Chairperson API Portal
   * Roles Allowed: TBA
   */
  require base_path('routes/chairpersons.php');


  /**
   * Route name: Coordinator Routes
   * API Route: Coordinator API Portal
   * Roles Allowed: TBA
   */
  require base_path('routes/coordinators.php');

  /**
   * Route name: Company Routes
   * API Route: Company API Portal
   * Roles Allowed: Admin, Dean, Chairperson
   */
  require base_path('routes/companies.php');

  /**
   * Route name: OSA Routes
   * API Route: OSA API Portal
   * Roles Allowed: Admin
   */
  Route::prefix('/osa')->middleware('role:admin')->group(function () {

    // GET
    Route::get('/', [OsaController::class, 'getAllOsaUsers']);

    // POST
    Route::post('/', [OsaController::class, 'addNewOsa']);

    /**
     * PUT
     */
    // RESTORE
    Route::put('/{osa_id}/restore', [OsaController::class, 'restoreOsaUserById']);

    Route::put('/{osa_id}', [OsaController::class, 'updateOsaUserById']);

    // DELETE
    Route::delete('/{osa_id}', [OsaController::class, 'deleteOsaUserById']);

   

  });

  // GET
  Route::get('/', [UserController::class, 'getAllUsers'])->middleware('role:admin');

  // SOFT DELETE
  Route::delete('/{user_id}/soft-delete', [UserController::class, 'softDeleteUserById'])->middleware('role:admin');
});
