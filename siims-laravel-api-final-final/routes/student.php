<?php

use App\Http\Controllers\StudentApplicationController;
use App\Http\Controllers\StudentApplicationDocumentSubmissionController;
use App\Http\Controllers\StudentDtrEntryController;
use App\Http\Controllers\StudentEducationController;
use App\Http\Controllers\StudentEndorsementLetterController;
use App\Http\Controllers\StudentWorkExperienceController;
use App\Http\Controllers\StudentWorkPostController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Support\Facades\Route;

// Student Routes
// resources: /student

Route::prefix('/student')->middleware('role:student')->group(function () {

  // get me student
  // resources: /student/auth
  Route::get('/auth', [StudentController::class, 'getAuthStudent']);

  // Profile
  // resources: /student/profile
  Route::prefix('/profile')->group(function () {
    Route::get('/', [StudentProfileController::class, 'getProfile']);
  });

  // work-experiences
  // resources: /student/work-experiences
  Route::prefix('/work-experiences')->group(function () {
    Route::put('/{work_experience_id}', [StudentWorkExperienceController::class, 'updateWorkExperienceById']);
    Route::delete('/{work_experience_id}', [StudentWorkExperienceController::class, 'deleteWorkExperienceById']);
    Route::post('/', [StudentWorkExperienceController::class, 'addNewWorkExperience']);
    Route::get('/', [StudentWorkExperienceController::class, 'getAllWorkExperiences']);
});

  // educations
  // resources: /student/educations
  Route::prefix('/educations')->group(function () {
    Route::put('/{education_id}', [StudentEducationController::class, 'updateEducationById']);
    Route::delete('/{education_id}', [StudentEducationController::class ,'deleteEducationById']);
    Route::post('/', [StudentEducationController::class, 'createNewEducation']);
    Route::get('/', [StudentEducationController::class, 'getAllEducations']);
  });

  // applications route
  // resources: /student/applications
  Route::prefix('/applications')->group(function () {

    
    // document submissions route
    // resources: /student/applications/{application_id}/document-submissions
    Route::prefix('/{application_id}/document-submissions')->group(function () {

      // Step 1 Document Submissions
      Route::get('/step-1/get', [StudentApplicationDocumentSubmissionController::class, 'getStepOneAllDocuments']);

      // Step 2 Document Submissions
      Route::get('/step-2/get', [StudentApplicationDocumentSubmissionController::class, 'getStepTwoAllDocuments']);

    });

    Route::post('/{application_id}/request-endorsement-letter', [StudentEndorsementLetterController::class, 'requestEndorsementLetterByApplicationId']);
    Route::post('/{application_id}/upload-document/{document_submission_id}', [StudentApplicationController::class, 'uploadDocument']);
    Route::post('/{application_id}/update-document/{document_submission_id}', [StudentApplicationController::class, 'updateUploadedDocument']);
    Route::get('/', [StudentApplicationController::class, 'getAllApplcations']);
    Route::get('/{application_id}', [StudentApplicationController::class, 'getApplicationById']);
 
  });

  // jobs
  // resources: /student/jobs
  Route::prefix('/jobs')->group(function () {

    // resources: /student/jobs/{job_id}/application
    /* Route::prefix('/{job_id}/application')->group(function () {

      // resources: /student/jobs/{job_id}/application/{application_id}/submit-document
      Route::prefix('/{application_id}')->group(function () {

        // Create/Submit new document
        Route::post('/', [StudentApplicationController::class, 'addNewDocument']);

      });

      // Request Endorsement Letter
      Route::post('/request-endorsement-letter', [StudentEndorsementLetterController::class, 'createRequestEndorsementLetter']);

      // Creates a new application
      Route::post('/', [StudentApplicationController::class, 'createNewApplication']);

    }); */

    // resources: /student/jobs/job_id/apply
    Route::post('/{job_id}/apply', [StudentApplicationController::class, 'applyJob']);

    // Student Apply a job
    // Route::post('/{job_id}/apply', [StudentApplicationController::class, 'applyJob']);

    // Student Request Endorsement
    Route::post('/{job_id}/request-endorsement', [StudentEndorsementLetterController:: class, 'requestEndorsement']);

    Route::get('/currently-applied', [StudentWorkPostController::class, 'getCurrentlyAppliedJob']);
    Route::get('/{job_id}', [StudentWorkPostController::class, 'getJob']);
    Route::get('/', [StudentWorkPostController::class, 'getAllJobs']);
  });

  // Daily Time Record
  // resources: /student/dtr-entries
  Route::prefix('/dtr-entries')->group(function () {
    Route::get('/{application_id}', [StudentDtrEntryController::class, 'getAllDtrEntries']);
    Route::post('/{application_id}', [StudentDtrEntryController::class, 'addNewDtrEntry']);
    Route::put('/{application_id}/{dtr_id}', [StudentDtrEntryController::class, 'updateDtrEntryById']);
    Route::delete('/{application_id}/{dtr_id}', [StudentDtrEntryController::class, 'deleteDtrEntryById']);
  });

  // Endorsement request
  // resources: /student/endorsement-letter-requests
  Route::prefix('/endorsement-letter-requests')->group(function () {

    // Request Endorsement Letter base on the Job ID
    // Route::post('/{job_id}', [StudentEndorsementLetterController::class, 'requestEndorsementLetterByJobId']);

    // Request Endorsement Letter base on the Application ID
    Route::post('/{application_id}', [StudentEndorsementLetterController::class, 'requestEndorsementLetterByApplicationId']);

    Route::get('/', [StudentEndorsementLetterController::class, 'index']);
    Route::post('/', [StudentEndorsementLetterController::class, 'store']);
  });
});
