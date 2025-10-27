<?php

use App\Http\Controllers\CompanyApplicantController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyOfficeController;
use App\Http\Controllers\CompanyOfficeTypeController;
use App\Http\Controllers\CompanySupervisorController;
use App\Http\Controllers\CompanyWorkPostController;
use Illuminate\Support\Facades\Route;

// Company Routes
// resources: /company
Route::prefix('/company')->middleware('role:company')->group(function () {

  // Profile
  Route::get('/profile', [CompanyController::class, 'profile']);

  // Office Types Routes
  // resources: /company/office-types
  Route::get('/office-types', [CompanyOfficeTypeController::class, 'index']);

  // Applicant Routes
  // resources: /company/applicants
  Route::prefix('/applicants')->group(function () {
    Route::get('/', [CompanyApplicantController::class, 'getAllApplicants']);
    Route::get('/{application_id}', [CompanyApplicantController::class, 'getApplicantApplicationById']);
    Route::post('/{application_id}/submit-acceptance-letter', [CompanyApplicantController::class, 'addAcceptanceLetter']);
    Route::put('/{application_id}/update/{document_id}', [CompanyApplicantController::class, 'updateApplicantApplicationById']);
    Route::put('/{application_id}/mark-as-approve', [CompanyApplicantController::class, 'markApplicationApprove']);
  });

  // Supervisor Routes
  // resources: /company/supervisors
  Route::prefix('/supervisors')->group(function () {
    Route::post('/', [CompanySupervisorController::class, 'store']);
    Route::get('/', [CompanySupervisorController::class, 'index']);
    Route::put('/{supervisor_id}', [CompanySupervisorController::class, 'update']);
  });

  // Office Routes
  // resources: /company/offices
  Route::prefix('/offices')->group(function () {
    Route::get('/', [CompanyOfficeController::class, 'getAllOffices']);
    Route::post('/', [CompanyOfficeController::class, 'addNewOffice']);
    Route::get('/{office_id}', [CompanyOfficeController::class, 'getOfficeById']);
    Route::put('/{office_id}', [CompanyOfficeController::class, 'updateOfficeById']);
    Route::delete('/{office_id}', [CompanyOfficeController::class, 'deleteOfficeById']);
  });

  // Work Post Routes
  // resources: /company/work-posts
  Route::prefix('/work-posts')->group(function () {
    Route::get('/', [CompanyWorkPostController::class, 'getAllWorkPosts']);
    Route::post('/', [CompanyWorkPostController::class, 'addNewWorkPost']);
    Route::get('/{work_post_id}',[CompanyWorkPostController::class, 'getWorkPostById']);
    Route::put('/{work_post_id}', [CompanyWorkPostController::class, 'updateWorkPostById']);
    Route::delete('/{work_post_id}', [CompanyWorkPostController::class, 'deleteWorkPostById']);
   
  });
});
