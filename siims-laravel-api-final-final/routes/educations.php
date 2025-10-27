<?php

use App\Http\Controllers\EducationController;
use Illuminate\Support\Facades\Route;

Route::prefix('/educations')->middleware('role:admin,student')->group(function () {

  /**
   * GET
   */
  Route::get('/', [EducationController::class, 'getAllEducations']);

   /**
    * POST
    */
   Route::post('/', [EducationController::class, 'addNewEducation'])->middleware('role:student');

    /**
     * PUT
     */
    Route::put('/{education_id}', [EducationController::class, 'updateEducation'])->middleware('role:student');

    /**
    * DELETE
    */
    Route::delete('/{education_id}', [EducationController::class, 'deleteEducation'])->middleware('role:student');

});