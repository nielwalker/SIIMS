<?php



use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\DocumentSubmissionController;
use App\Http\Controllers\StudentApplicationController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\FileUploadController;
use Illuminate\Support\Facades\Route;


// Application Routes
Route::prefix('/applications')->middleware('role:admin,company,coordinator,osa,student,supervisor')->group(function () {

  /**
   * GET
   */
  Route::get('/{application_id}/documents', [DocumentSubmissionController::class, 'getAllDocumentsByApplicationID'])->middleware('role:company,osa,coordinator');
  Route::get('/{application_id}/daily-time-records', [DtrController::class, 'getDailyTimeRecordsByApplicationID']);
  Route::get('/latest', [ApplicationController::class, 'getLatestApplication'])->middleware('role:student');
  Route::get('/latest-application-id', [ApplicationController::class, 'getLatestApplicationId'])->middleware('role:student');


  Route::get('/{application_id}', [ApplicationController::class, 'getByID'])->middleware('role:student');

  /**
   * Step Documents
   */
  Route::get('/{application_id}/step-1/get', [DocumentSubmissionController::class, 'getStepOneAllDocuments']);
  Route::get('/{application_id}/step-2/get', [DocumentSubmissionController::class, 'getStepTwoAllDocuments']); 
  
 
  // Route::get('/{application_id}', [ApplicationController::class, 'getByID']);
  

  /**
   * POST
   */
  Route::post('/{application_id}/submit-acceptance-letter', [ApplicationController::class, 'addAcceptanceLetter']);
  Route::post('/{application_id}/upload-document', [FileUploadController::class, 'upload']);

  // Route::post('/{work_post_id}/apply', [ApplicationController::class, 'apply']);

  Route::post('/{work_post_id}', [ApplicationController::class, 'create']);

  // FOR OSA

  

 


  /**
   * PUT
   */
  
  Route::put('/mark-as-ready-to-deploy', [ApplicantController::class, 'markAsReadyToDeploy']);
  Route::put('/{application_id}/mark-as-approve', [ApplicationController::class, 'markApproveApplication'])->middleware('role:admin,company');  
  Route::put('/{application_id}/mark-as-rejected', [ApplicationController::class, 'markRejectApplication'])->middleware('role:admin,company');  


  Route::put('/{application_id}/mark-all-step-2-documents-as-approve', [ApplicationController::class, 'approveAllStepTwoApplication'])->middleware('role:admin,osa');

  

  /**
   * Submitting Documents
   */
  Route::post('/{application_id}/upload-document/{document_submission_id}', [StudentApplicationController::class, 'uploadDocument'])->middleware('role:student');

  // Withdraw of Application
  Route::put('/{application_id}/withdraw', [ApplicationController::class, 'withdrawApplication']);
});
