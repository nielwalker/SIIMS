<?php

use App\Http\Controllers\WeeklyEntryController;
use Illuminate\Support\Facades\Route;

/**
 * Reports Routes Here
 */
Route::prefix('/weekly-accomplishment-reports')->group(function () {

  /**
   * GET
   */
  Route::get('/{application_id}', [WeeklyEntryController::class, 'getAllWeeklyEntries']);
  /**
   * POST
   */
  Route::post('/{application_id}', [WeeklyEntryController::class, 'addNewWeekEntry']);
});




// TODO: This is Final Should Be.

Route::prefix('/{application_id}/weekly-accomplishment-reports')->middleware('role:admin,student,coordinator,company,supervisor')->group(function () {

  /**
   * GET
   */
  Route::get('/', [WeeklyEntryController::class, 'getAllWeeklyEntries']);
  /**
   * POST
   */
  Route::post('/', [WeeklyEntryController::class, 'addNewWeekEntry'])->middleware('role:admin,student');

  /**
   * PUT 
   */
  Route::put('/{weekly_entry_id}', [WeeklyEntryController::class, 'updateWeekEntryById'])->middleware('role:admin,student');

  /**
   * Delete
   */
  Route::delete('/{weekly_entry_id}', [WeeklyEntryController::class, 'deleteWeekEntryById'])->middleware('role:admin,student');
});