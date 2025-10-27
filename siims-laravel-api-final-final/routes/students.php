<?php

use App\Http\Controllers\AssignStudentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\PDFAssignStudentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentV2Controller;
use App\Http\Controllers\StudentV3Controller;
use Illuminate\Support\Facades\Route;


// V3
Route::prefix('/v3/students')->group(function () {

  /**
   * 
   * GET
   * 
   * 
   */
  Route::get('/', [StudentV3Controller::class, 'get'])->middleware('role:admin,dean,chairperson,coordinator');

});

// V2
Route::prefix('/v2/students')->group(function () {

  /**
   * GET
   */
  Route::get('/', [StudentV2Controller::class, 'getAllStudents'])->middleware('role:admin,dean,chairperson,coordinator');

});


/**
 * Student Group Routes
 */
Route::prefix('/students')->group(function () {

  /**
   * GET
   */
  // Route::get('/{program_id}', [StudentController::class, ]);
  Route::get('/search', [StudentController::class, 'searchStudent'])->middleware('role:admin,chairperson');

  Route::get('/home', [StudentController::class, 'home'])->middleware('role:student');
  Route::get('/home-fetch-jobs', [StudentController::class, 'homeFetchJobs'])->middleware('role:student');
  Route::get('/my-reports', [StudentController::class, 'getAllMyLatestDocuments'])->middleware('role:student');
  Route::get('/dean', [StudentController::class, 'getAllStudentsByDean']);
  Route::get('/get-all-students', [StudentController::class, 'getAllStudents'])->middleware('role:admin,chairperson');

   /**
   * Fetch Status
   */
  Route::get('/get-student-status-id', [StudentController::class, 'getStudentStatusId'])->middleware('role:student');
 
  Route::get('/{student_id}/check-apply-status', [StudentController::class, 'checkIfStudentCanApply']);

  /**
   * * Routes for those statuses in Student.
   * ! Roles: Admin, Coordinator Only
   */
  Route::get('/not-yet-applied', [StudentController::class, 'getAllStudentsNotYetAppliedStudents'])->middleware('role:admin,coordinator');
  Route::get('/enrolled', [StudentController::class, 'getEnrolledStudents'])->middleware('role:admin,coordinator');
  Route::get('/pending-approval', [StudentController::class, 'getPendingApprovalStudents'])->middleware('role:admin,coordinator');
  Route::get('/ready-for-deployment', [StudentController::class, 'getReadyForDeploymentStudents'])->middleware('role:admin,coordinator');
  Route::get('/active', [StudentController::class, 'getActiveStudents'])->middleware('role:admin,coordinator');
  Route::get('/completed', [StudentController::class, 'getCompletedStudents'])->middleware('role:admin,coordinator');


  Route::get('/get-active-students', [StudentController::class, 'getAllActiveStudents'])->middleware('role:admin,coordinator,supervisor,company');

  Route::get('/archives', [StudentController::class, 'getAllStudentArchives'])->middleware('role:admin');

  /**
   * Get all Students
   */
  Route::get('/', [StudentController::class, 'getAllStudents'])->middleware('role:admin,dean,chairperson,coordinator');

  /**
   * POST
   */
  Route::post('/import-all', [StudentController::class, 'import'])->middleware('role:admin,dean,chairperson');
  Route::post('/', [StudentController::class, 'addNewStudent'])->middleware('role:admin,dean,chairperson');

  /**
   * PUT
   */
  // Route::put('/assign-to-coordinator', [ChairpersonStudentController::class, 'assignStudents']);
  Route::post('/import-students-assign', [PDFAssignStudentController::class, 'importClasslist'])->middleware('role:coordinator');
  Route::put('/mark-ready-for-deployment', [StudentController::class, 'markAsReadyForDeployment'])->middleware('role:coordinator');
  Route::put('/assign-to-coordinator', [AssignStudentController::class, 'assignStudents'])->middleware('role:admin,chairperson');
  Route::put('/{student_id}', [StudentController::class, 'updateStudentByID'])->middleware('role:admin');
  Route::put('/', [StudentController::class, 'assignToCoordinator']);

  /**
   * DELETE
   */
  Route::delete('/{student_id}', [StudentController::class, 'deleteStudentByID'])->middleware('role:admin');

  /**
   * UPLOAD
   */
 
  Route::post('/{program_id}/upload-students', [FileController::class, 'uploadStudents'])->middleware('role:admin,dean,chairperson');
  Route::post('/verify', [FileController::class, 'uploadVerifyStudents'])->middleware('role:admin');
});
