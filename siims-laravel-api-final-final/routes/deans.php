<?php

use App\Http\Controllers\DeanController;
use App\Http\Controllers\DeanV2Controller;
use Illuminate\Support\Facades\Route;


// Version 2.0
Route::prefix('/v2/deans')->middleware('role:admin')->group(function () {

  /**
   * PUT
   */
  Route::put('/{dean_id}/restore', [DeanV2Controller::class, 'restoreDeanByID']);
  Route::put('/{dean_id}', [DeanV2Controller::class, 'updateDean']);


  /**
   * GET
   */
  Route::get('/', [DeanV2Controller::class, 'getAllDeans']);

  /**
   * DELETE
   */
  Route::delete('/{dean_id}', [DeanV2Controller::class, 'deleteDean']);

});

// Version 1.0
Route::prefix('/deans')->middleware('role:admin')->group(function () {
  /**
   * GET
   */
  Route::get('/including-colleges', [DeanController::class, 'getAllDeansIncludingCollege']);
  Route::get('/', [DeanController::class, 'getAllDeans'])->middleware('role:admin');

  /**
   * POST
   */
  Route::post('/', [DeanController::class, 'addNewDean']);

  /**
   * PUT
   */
  Route::put('/{dean_id}', [DeanController::class, 'updateDean']);

  /**
   * DELETE
   */
  Route::delete('/{dean_id}', [DeanController::class, 'deleteDean']);
});
