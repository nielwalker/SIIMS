<?php

use App\Http\Controllers\SupervisorApplicantController;
use App\Http\Controllers\SupervisorWorkPostController;
use App\Http\Controllers\SupervisorWorkTypeController;
use Illuminate\Support\Facades\Route;

// Supervisor Routes
// resources: /supervisor
Route::prefix('/supervisor')->middleware('role:supervisor')->group(function () {

  // resources: /supervisor/work-types
  Route::get('/work-types', [SupervisorWorkTypeController::class, 'index']);

  // Applicant Routes
  // resources: /supervisor/applicants
  Route::prefix('/applicants')->group(callback: function () {
    Route::get('/', [SupervisorApplicantController::class, 'getAllApplicants']);
    Route::get('/{application_id}', [SupervisorApplicantController::class, 'getApplicantApplicationById']);
  });

  // resources: /supervisor/work-posts
  Route::prefix('/work-posts')->group(function () {

    // Applicant Routes
    /* Route::prefix('/{job_id}/applicants')->group(function () {
      
      Route::get('/', [SupervisorApplicantController::class, 'getAllApplicants']);
    }); */

    Route::get('/{job_id}', [SupervisorWorkPostController::class, 'getJobById']);
    Route::get('/', [SupervisorWorkPostController::class, 'getAllJobs']);
    Route::post('/', [SupervisorWorkPostController::class, 'store']);
    Route::put('/{work_post_id}', [SupervisorWorkPostController::class, 'updateWorkPostById']);
    Route::delete('/{work_post_id}', [SupervisorWorkPostController::class, 'deleteWorkPostById']);
  });

});