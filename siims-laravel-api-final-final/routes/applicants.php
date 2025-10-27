<?php

use App\Http\Controllers\ApplicantController;
use Illuminate\Support\Facades\Route;

// Applicants
Route::prefix('/applicants')->group(function () {



  /**
   * Fetching
   */
  Route::get('/rejected', [ApplicantController::class, 'getRejectedApplicants'])->middleware('role:osa,company,coordinator');
  Route::get('/withdrawn', [ApplicantController::class, 'getWithdrawnApplicants'])->middleware('role:osa,company,coordinator');
  Route::get('/ready-for-deployment', [ApplicantController::class, 'getAllReadyForDeploymentApplicants'])->middleware('role:osa');
  Route::get('/approved', [ApplicantController::class, 'getAllApprovedApplicants'])->middleware('role:company,osa');
  Route::get('/pending', [ApplicantController::class, 'getAllPendingApplicants'])->middleware('role:company');
  Route::get('/', [ApplicantController::class, 'getAllApplicants'])->middleware('role:company,osa');
 

  /**
   * GET
   */
  Route::get('/{applicant_id}', [ApplicantController::class, 'getApplicantbyId'])->middleware('role:osa,company,coordinator');
});