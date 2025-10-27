<?php

use App\Http\Controllers\ChairpersonController;
use App\Http\Controllers\ChairpersonV2Controller;
use Illuminate\Support\Facades\Route;

// Version 2.0
Route::prefix('/v2/chairpersons')->middleware('role:admin,dean,chairperson')->group(function () {

  /**
   * GET
   */
  Route::get('/', [ChairpersonV2Controller::class, 'getAllChairpersons'])->middleware('role:admin,dean');


  /**
   * PUT
   */
  Route::put('/{chairperson_id}/restore', action: [ChairpersonV2Controller::class, 'restoreChairpersonByID']);

});

// Version 1.0
Route::prefix('/chairpersons')->middleware('role:admin,dean,chairperson')->group(function () {
  
  /**
   * GET
   */
  Route::get('/including-programs', [ChairpersonController::class, 'getAllChairpersonsIncludingProgram'])->middleware('role:admin,dean');
  Route::get('/current-program-id', [ChairpersonController::class, 'getChairpersonCurrentProgramId']);
  Route::get('/', [ChairpersonController::class, 'getAllChairpersons'])->middleware('role:admin,dean');

  /**
   * PUT
   */
  Route::put('/assign-students', [ChairpersonController::class, 'assignStudents']);
  Route::put('/{chairperson_id}', [ChairpersonController::class, 'updateChairpersonById']);

  /**
   * POST
   */
  Route::post('/', [ChairpersonController::class, 'addNewChairperson'])->middleware('role:admin');

  /**
   * DELETE
   */
  Route::delete('/{chairperson_id}', [ChairpersonController::class, 'deleteChairperson']);
});
