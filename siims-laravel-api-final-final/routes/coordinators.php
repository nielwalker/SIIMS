<?php

use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\CoordinatorV2Controller;
use App\Http\Controllers\FileController;
use App\Http\Controllers\StudentReportController;
use Illuminate\Support\Facades\Route;


// Version 2.0
Route::prefix('/v2/coordinators')->group(function () {

  /**
   * GET
   */
  Route::get('/search', [CoordinatorV2Controller::class, 'searchCoordinator']);
  Route::get('/', [CoordinatorV2Controller::class, 'getAllCoordinators'])->middleware('role:admin,dean,chairperson');

  /**
   * POST
   */
  Route::post('/', [CoordinatorV2Controller::class, 'addNewCoordinator'])->middleware('role:admin,dean,chairperson');

  /**
   * PUT
   */
  Route::put('/{coordinator_id}/restore', [CoordinatorV2Controller::class, 'restoreCoordinatorByID'])->middleware('role:admin');
  Route::put('/{coordinator_id}', [CoordinatorV2Controller::class, 'updateCoordinatorById'])->middleware('role:admin');

  /**
   * DELETE
   */
  Route::delete('/{coordinator_id}', [CoordinatorV2Controller::class, 'deleteCoordinatorById'])->middleware('role:admin');

});

/**
 * Coordinator Routes
 */

Route::prefix('/coordinators')->group(function () {
  /**
   * GROUP 
   */
  Route::prefix('/students')->group(function () {

    /**
     * GET
     */
    Route::get('/reports', [StudentReportController::class, 'getStudentReports'])->middleware('role:admin,coordinator,supervisor,company');
  });


  /**
   * GET
   */
  Route::get('/college', [CoordinatorController::class, 'getAllCoordinatorsByCollegeId']);
  Route::get('/lists', [CoordinatorController::class, 'getAllListsOfCoordinators'])->middleware('role:admin,dean,chairperson');

  Route::get('/{program_id}', [CoordinatorController::class, 'getAllCoordinatorsByProgramId']);
  
  Route::get('/', [CoordinatorController::class, 'getAllCoordinators'])->middleware('role:admin,dean,chairperson');


  /**
   * POST
   */
  Route::post('/', [CoordinatorController::class, 'addNewCoordinator'])->middleware('role:admin,dean,chairperson');

  /**
   * PUT
   */
  Route::put('/{coordinator_id}', [CoordinatorController::class, 'updateCoordinatorById'])->middleware('role:admin');

  /**
   * DELETE
   */
  Route::delete('/{coordinator_id}', [CoordinatorController::class, 'deleteCoordinatorById'])->middleware('role:admin');

  /**
   * UPLOAD
   */
  Route::post('/{program_id}/upload-coordinators', [FileController::class, 'uploadCoordinators']);
});
