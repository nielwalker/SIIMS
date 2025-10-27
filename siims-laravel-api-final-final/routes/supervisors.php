<?php

/**
 * Supervisor Group Routes
 */

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\InternController;
use App\Http\Controllers\InternWeeklyReportController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\SupervisorV2Controller;
use Illuminate\Support\Facades\Route;

// Version 2.0
Route::prefix('/v2/supervisors')->middleware('role:admin,company')->group(function () {

  /**
   * POST 
   */
  Route::post('/', [SupervisorV2Controller::class, 'addNewSupervisor']);

  /**
   * PUT
   */
  Route::put('/{supervisor_id}/restore', [SupervisorV2Controller::class, 'restoreSupervisorByID']);


  /**
   * GET
   */
  Route::get('/', [SupervisorV2Controller::class, 'getAllSupervisors']);

});

// Version 1.0
Route::prefix('/supervisors')->group(function () {

  /**
   * GET Group Routes
   */
  Route::prefix('/interns')->group(function () {



    /**
     * Daily Time Records
     */
    Route::prefix('/daily-time-records')->group(function () {

      /**
       * GET
       */
      Route::get('/{application_id}', [InternController::class, 'getAllInternsDailyTimeRecords']);

      /**
       * PUT
       */
      Route::put('/{dtrId}/mark', [InternController::class, 'markStatus']);
    });

    /**
     * Weekly Accomplishment Reports
     */
    Route::prefix('/weekly-accomplishment-reports')->group(function () {

      /**
       * GET
       */
      Route::get('/{application_id}', [InternWeeklyReportController::class, 'getAllWeeklyReports']);
    });


    /**
     * GET
     */
    Route::get('/get-all-on-going-interns', [ApplicantController::class, 'getAllAInternsOnGoing']);
    Route::get('/', [InternController::class, 'getAllInterns']);
  });

  /**
   * GET
   */
  Route::get('/', [SupervisorController::class, 'getAllSupervisors'])->middleware('role:admin,company');

  /**
   * POST
   */
  Route::post('/', [SupervisorController::class, 'addNewSupervisor']);

  /**
   * PUT
   */
  Route::put('/{supervisor_id}', [SupervisorController::class, 'updateSupervisorById'])->middleware('role:admin,company');

  /**
   * DELETE
   */
  Route::delete('/{supervisor_id}', [SupervisorController::class, 'deleteSupervisorById'])->middleware('role:admin,company');
});
