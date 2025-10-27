<?php

use App\Http\Controllers\CollegeController;
use App\Http\Controllers\CollegeV3Controller;
use Illuminate\Support\Facades\Route;

// College Routes

Route::prefix('/v3/colleges')->middleware('role:admin,dean')->group(function () {

  // GET
  Route::get('/', [CollegeV3Controller::class, 'get'])->middleware('role:admin');

});

Route::prefix('/colleges')->middleware('role:admin,dean')->group(function () {

  // GET
  Route::get('/lists', [CollegeController::class, 'getAllListsOfColleges']);
  Route::get('/', [CollegeController::class, 'getAllColleges'])->middleware('role:admin');

  // PUT
  Route::put('/{college_id}', [CollegeController::class, 'updateCollegeById'])->middleware('role:admin,dean');

  // POST
  Route::post('/', [CollegeController::class, 'create'])->middleware('role:admin');

  // DELETE
  Route::delete('/{college_id}', [CollegeController::class, 'deleteCollegeById'])->middleware('role:admin');
});
