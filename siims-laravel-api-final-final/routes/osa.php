<?php

// OSA Routes

use App\Http\Controllers\OsaApplicantController;
use App\Http\Controllers\OsaApplicationController;
use App\Http\Controllers\OsaDocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('/osa')->middleware('role:osa')->group(function () {

  // Application Routes
  // resoureces: /applications
  Route::prefix('/applications')->group(function () {
    // View application directly
    Route::get('/{application_id}', [OsaApplicationController::class, 'getApplicationById']);
  });

  // Applicants Routes
  // resources: /applicants
  Route::prefix('/applicants')->group(function () {

    // Application Routes
    // resources: /applicants/{applicatoin_id}/applications
    Route::prefix('/{applicant_id}/applications')->group(function () {

      Route::get('/', [OsaApplicantController::class, 'getApplicantApplications']);
      Route::get('/{application_id}', [OsaApplicantController::class, 'getApplicantApplicationById']);
      Route::post('/{application_id}/create-document',[OsaApplicantController::class, 'addNewDocument']); 
      Route::put('/{application_id}/update-document/{document_id}',[OsaApplicantController::class, 'updateDocumentById']); 

      // Mark Status
      // Mark one by one
      Route::put('/{application_id}/status/{document_id}', [OsaApplicantController::class, 'updateDocumentStatus']);
    });

    // Get all applicants
    Route::get('/', [OsaApplicantController::class, 'getAllApplicants']);
    
  });

  // Document Type Routes
  // resources: /document-types
  Route::prefix('/document-types')->group(function () {
    Route::get('/', [OsaDocumentTypeController::class, 'getAllDocumentTypes']);
    Route::post('/', [OsaDocumentTypeController::class, 'addNewDocumentType']);
    Route::put('/{document_type_id}', [OsaDocumentTypeController::class, 'updateDocumentTypeById']);
    Route::delete('/{document_type_id}', [OsaDocumentTypeController::class, 'deleteDocumentTypeById']);
  });
});
