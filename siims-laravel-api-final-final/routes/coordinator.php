<?php

use App\Http\Controllers\CoordinatorProfileController;
use App\Http\Controllers\CoordinatorStudentApplicationController;
use App\Http\Controllers\CoordinatorStudentController;
use Illuminate\Support\Facades\Route;

// Coordinator Routes
// resources: /coordinator

Route::prefix('/coordinator')->middleware('role:coordinator')->group(function () {

  // My profile
  // resources: /coordinator/profile
  Route::prefix('/profile')->group(function () {

    Route::get('/', [CoordinatorProfileController::class, 'getProfile']);
    Route::put('/', [CoordinatorProfileController::class, 'updateProfile']);

  });

  // Student Route
  // resources: /coordinator/students
  Route::prefix('/students')->group(function () {
    Route::get('/', [CoordinatorStudentController::class, 'getAllStudents']);
    Route::get('/{student_id}', [CoordinatorStudentController::class, 'getStudentById']);

     // Mark student to deploy - Status ID 12 - Deployed
     Route::put('/deploy-students', [CoordinatorStudentController::class, 'updateStudentStatus']);

    // Application Route
    // resources: /coordinator/students/{student_id}/applications
    Route::prefix('/{student_id}/applications')->group(function () {
      Route::get('/', [CoordinatorStudentApplicationController::class, 'getStudentApplications']);
      Route::get('/{application_id}', [CoordinatorStudentApplicationController::class, 'getStudentApplicationById']);
    });

    
    
  });

});