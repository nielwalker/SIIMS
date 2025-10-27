<?php

use App\Http\Controllers\DeanProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileV2Controller;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\TestProfileController;
use Illuminate\Support\Facades\Route;

// Profile Routes
// resources: /profile
Route::prefix('/profiles')->group(function () {

  // Group Routes
  Route::prefix('/views')->group(function () {

    /**
     * View Dean
     */
    Route::get('/deans/{dean_id}', [TestProfileController::class, 'getDeanProfile'])->middleware('role:admin');
    /**
     *  View Chairperson
     */
    Route::get('/chairpersons/{chairperson_id}', [TestProfileController::class, 'getChairpersonProfile'])->middleware('role:admin');
    /**
     * View Coordinator
     */
    Route::get('/coordinators/{coordinator_id}', [TestProfileController::class, 'getCoordinatorProfile']);
    /**
     * View Student
     */
    Route::get('/students/{student_id}', [TestProfileController::class, 'getStudentProfile']);
    /**
     * View Company
     */
    Route::get('/companies/logo-url', [TestProfileController::class, 'getCompanyLogoUrl'])->middleware('role:company');
    Route::get('/companies/my', [TestProfileController::class, 'getMyCompanyProfile']);
    Route::get('/companies/{company_id}', [TestProfileController::class, 'getCompanyProfile']);
    Route::get('/', [TestProfileController::class, 'getUserProfile']);

  });

  /**
   * PUT
   */
  Route::post('/update-logo', [TestProfileController::class, 'updateCompanyLogo'])->middleware('role:admin,company');
  Route::post('/update-profile/{user_id}', [TestProfileController::class, 'updateUserProfileImage']);
  Route::post('/update-cover/{user_id}', [TestProfileController::class, 'updateUserCoverImage']);
  Route::put('/update/{user_id}', [TestProfileController::class, 'updateProfile']);
  // Route::put('/{user_id}', [DeanProfileController::class, 'updateProfile']);

  /**
   * GET
   */
  Route::get('/self', [TestProfileController::class, 'getSelfProfile']);
  Route::get('/student', [StudentProfileController::class, 'getProfile'])->middleware('role:student');
  Route::get('/dean', [DeanProfileController::class, 'getProfile'])->middleware('role:dean');
  // Route::get('/', [ProfileController::class, 'getProfile']);
  Route::get('/', [ProfileV2Controller::class, 'get']);

  /**
   * POST
   */
  Route::post('/update-student-profile', [ProfileController::class, 'updateStudentProfile']);
  Route::post('/update-student-profile', [ProfileController::class, 'updateStudentProfile']);

  
});
