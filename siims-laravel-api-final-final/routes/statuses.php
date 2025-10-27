<?php

use App\Http\Controllers\ApplicationStatusController;
use App\Http\Controllers\DocumentStatusController;
use App\Http\Controllers\DocumentSubmissionStatusController;
use App\Http\Controllers\DtrStatusController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\StudentStatusController;
use Illuminate\Support\Facades\Route;


// Status Routes
// resources: /statuses

Route::prefix('/statuses')->group(function () {

  /**
   * GET
   */
  // All Student Statuses Lists
  Route::get('/student-status-lists', [StudentStatusController::class, 'getAllStudentStatuses']);

  // All Application Statuses Lists
  Route::get('/application-status-lists', [ApplicationStatusController::class, 'getAllApplicationStatuses']);

  // All Daily Time Record Statuses Lists
  Route::get('/daily-time-record-status-lists', [DtrStatusController::class, 'getAllDailyTimeRecordStatuses']);
 
  // All Document Status Lists
  Route::get('/document-statuses', [StatusController::class, 'getAllDocumentStatuses']);

  // * New: All Document Status Lists
  Route::get('/document-submission-statuses', [DocumentStatusController::class, 'getAllDocumentSubmissionStatuses']);


  // All Statuses
  Route::get('/', [StatusController::class, 'getAllStatuses']);
});
