<?php

use App\Http\Controllers\DocumentSubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('/documents')->group(function () {

  // Mark Document Status
  Route::put('/{document_id}/update-status', [DocumentSubmissionController::class, 'markDocumentStatus'])->middleware('role:company,osa');

});