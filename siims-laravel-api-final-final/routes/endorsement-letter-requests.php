<?php



use App\Http\Controllers\EndorsementLetterRequestController;
use App\Http\Controllers\EndorsementLetterRequestV2Controller;
use Illuminate\Support\Facades\Route;

// Endorsement-Letter-Requests (Version 2)
Route::prefix('/v2/endorsement-letter-requests')->group(function () {

  // GET
  Route::get('/{endorsment_request_id}', [EndorsementLetterRequestV2Controller::class, 'getEndorsementRequestByID'])->middleware('role:chairperson');
});

// Endorsement-Letter-Requests (Version 1)
Route::prefix('/endorsement-letter-requests')->group(function () {

  /**
   * 
   * DELETE
   * 
   */
  Route::delete('/{endorsment_request_id}', [EndorsementLetterRequestController::class, 'delete'])->middleware('role:admin,chairperson');

  /**
   * POST
   */

  Route::post('/{endorsement_request_id}/dean-submit-endorsement-letter', [EndorsementLetterRequestController::class, 'deanSubmitEndorsementLetter']);
  Route::post('/manual', [EndorsementLetterRequestController::class, 'manualAddNewRequest'])->middleware('role:admin,chairperson');
  Route::post('/', [EndorsementLetterRequestController::class, 'create']);

  /**
   * PUT
   */
  // FOR Admin, Dean, and Chairperson
  Route::put('/mark-as-approval', [EndorsementLetterRequestController::class, 'markAsApproval']);
  Route::put('/mark-as-approved', [EndorsementLetterRequestController::class, 'markAsApproved']);

  Route::put('/{endorsment_request_id}/restore', [EndorsementLetterRequestController::class, 'restoreByID'])->middleware('role:admin,chairperson');

  /**
   * GET
   */
  // FOR Student
  Route::get('/current', [EndorsementLetterRequestController::class, 'getCurrentEndorsementLetterRequestStatus'])->middleware('role:student');
  // FOR Admin and Dean only
  Route::get('/get-waiting-for-approval-letter-requests', [EndorsementLetterRequestController::class, 'getAllWaitingForApprovalEndorsementLetterRequests'])->middleware('role:admin,dean');

  Route::get('/withdrawn', [EndorsementLetterRequestController::class, 'getAllEndorsementLetterRequestsWithdrawn'])->middleware('role:chairperson,dean');
  Route::get('/pending', [EndorsementLetterRequestController::class, 'getAllEndorsementLetterRequestsPending']);
  Route::get('/pending-approval', [EndorsementLetterRequestController::class, 'getAllEndorsementLetterRequestsPendingApproval']);
  Route::get('/approved', [EndorsementLetterRequestController::class, 'getAllEndorsementLetterRequestsApproved']);


  Route::get('/{endorsement_request_id}', [EndorsementLetterRequestController::class, 'getEndorsementLetterRequest'])->middleware('role:admin,dean,chairperson');
  Route::post('/{endorsement_request_id}/upload', [EndorsementLetterRequestController::class, 'addEndorsementLetter'])->middleware('role:admin,dean,chairperson');


  Route::get('/', [EndorsementLetterRequestController::class, 'get']);
});
