<?php

use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\StudentHomePageController;
use Illuminate\Support\Facades\Route;

Route::prefix('/homes')->group(function () {

  // STUDENT HOMEPAGE
  Route::get('/student', [StudentHomePageController::class, 'getHome'])->middleware('role:student');

});
    