<?php

use App\Http\Controllers\DocumentTrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('/trackings')->middleware('role:student')->group(function () {

  // GET
  Route::get('/', [DocumentTrackingController::class, 'getAllTrackingDocuments']);

});