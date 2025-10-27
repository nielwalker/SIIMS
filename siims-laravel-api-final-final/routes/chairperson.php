<?php

use App\Http\Controllers\ChairpersonCompanyController;
use App\Http\Controllers\ChairpersonCoordinatorController;
use App\Http\Controllers\ChairpersonEndorsementLetterRequestController;
use App\Http\Controllers\ChairpersonStudentController;
use App\Http\Controllers\FileUploadController;
use Illuminate\Support\Facades\Route;


// Chairperson Routes
// resources: /chairperson
Route::prefix('/chairperson')->middleware('role:chairperson')->group(function () {

  // Testing Upload
    Route::post('/upload-pdf', [FileUploadController::class, 'testing']);

  // Coordinator Route
  // resources: /chairperosn/coordinators
  Route::prefix('/coordinators')->group(function () {
    Route::get('/', [ChairpersonCoordinatorController::class, 'getAllCoordinators']);
  });

  // Companies Route
  // resources: /chairperson/companies
  Route::prefix('/companies')->group(function () {

    // Offices Route
    // resources: /chairperson/company_id/offices
    Route::prefix('/{company_id}/offices')->group(function () {
      Route::get('/', [ChairpersonCompanyController::class, 'getAllOffices']);
    });

    Route::get('/', [ChairpersonCompanyController::class, 'index']);
    Route::get('/{company_id}', [ChairpersonCompanyController::class, 'show']);
    Route::post('/', [ChairpersonCompanyController::class, 'store']);
  });

    // Students Route
    // resources: /chairperson/students
    Route::prefix('/students')->group(function () {
      Route::get('/', [ChairpersonStudentController::class, 'getAllStudentsByProgram']);
      Route::post('/', [ChairpersonStudentController::class, 'addNewStudent']);
      Route::put('/assign-to-coordinator', [ChairpersonStudentController::class, 'assignStudents']);
      Route::post('/import-students', [ChairpersonStudentController::class, 'importStudents']);
    });

    // Endorsement Route
    // resources: /chairperson/endorsement-letter-requests
    Route::prefix('/endorsement-letter-requests')->group(function () {
        Route::get('/{endorsement_request_id}', [ChairpersonEndorsementLetterRequestController::class, 'getEndorsementLetterRequest']);
        Route::post('/{endorsement_request_id}/upload', [ChairpersonEndorsementLetterRequestController::class, 'addEndorsementLetter']);
        Route::post('/testing', [ChairpersonEndorsementLetterRequestController::class, 'testing']);
        Route::get('/', [ChairpersonEndorsementLetterRequestController::class, 'getAllEndorsementLetterRequests']);
        Route::post('/file-upload', [FileUploadController::class, 'FileUpload']);
    });
});
