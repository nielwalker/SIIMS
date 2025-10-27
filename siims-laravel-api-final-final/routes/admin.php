<?php

use App\Http\Controllers\AdminCampusController;
use App\Http\Controllers\AdminChairpersonController;
use App\Http\Controllers\AdminCollegeController;
use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\AdminCompanyOfficeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCoordinatorController;
use App\Http\Controllers\AdminDeanController;
use App\Http\Controllers\AdminDocumentTypeController;
use App\Http\Controllers\AdminFolderController;
use App\Http\Controllers\AdminOfficeController;
use App\Http\Controllers\AdminOfficeTypeController;
use App\Http\Controllers\AdminProgramController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminStorageController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AdminSupervisorController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

// Admin Routes
Route::prefix('/admin')->middleware('role:admin')->group(function () {

  // Dashboard
  // resources: /admin/dashboard
  Route::get('/dashboard', [AdminController::class, 'dashboard'])->middleware('role:admin,company,dean,supervisor,chairperson,coordinator,osa');

  // Profile
  // resources: /admin/profile
  // TODO
  Route::prefix('/profile')->group(function () {
    Route::get('/', [AdminController::class, 'profile']);
    Route::put('/', [AdminController::class, 'updateProfile']);
  });

  // Logs route
  // resources: /admin/logs
  Route::prefix('/logs')->middleware('role:admin')->group(function () {
    Route::get('/', [LogController::class, 'getAllLogs']);
  });

  // Campus Route
  // resources: /admin/campuses
  Route::prefix('/campuses')->group(function () {
    Route::get('/', [AdminCampusController::class, 'index']);
    Route::get('/{campus_id}', [AdminCampusController::class, 'show']);
    Route::post('/', [AdminCampusController::class, 'store']);
    Route::put('/{campus_id}', [AdminCampusController::class, 'update']);
    Route::delete('/{campus_id}', [AdminCampusController::class, 'destroy']);
  });

  // College Route
  // resources: /admin/colleges
  Route::prefix('/colleges')->group(function () {

    // Program Route
    // resources: /admin/colleges/{college_id}/programs
    Route::prefix('/{college_id}/programs')->group(function () {
      Route::get('/', [AdminProgramController::class, 'getAllProgramsByCollegeId']);
    });

    // Route::post('/archive/selected', [AdminCollegeController::class, 'archiveColleges']);
    // Route::delete('/archive/{college_id}', [AdminCollegeController::class, 'archive']);
    Route::get('/', [AdminCollegeController::class, 'getAllColleges']);
    Route::get('/{college_id}', [AdminCollegeController::class, 'show']);
    Route::post('/', [AdminCollegeController::class, 'addNewCollege']);
    Route::put('/{college_id}', [AdminCollegeController::class, 'updateCollegeById']);
  });

  // Program Route
  // resources: /admin/programs
  Route::prefix('/programs')->group(function () {
    Route::put('/{program_id}', [AdminProgramController::class, 'updateProgramById']);
    Route::delete('/{delete_id}', [AdminProgramController::class, 'deleteProgramById']);
    Route::get('/', [AdminProgramController::class, 'getAllPrograms']);
    Route::post('/', [AdminProgramController::class, 'addNewProgram']);
  });

  // Office Route
  // resources: /admin/offices
  Route::prefix('/offices')->group(function () {

    // Office Types Route
    // resources: /admin/offices/types
    Route::prefix('/types')->group(function () {
      Route::get('/', [AdminOfficeTypeController::class, 'index']);
    });

    Route::put('/{office_id}', [AdminOfficeController::class, 'update']);
    Route::post('/', [AdminOfficeController::class, 'store']);
    Route::get('/', [AdminOfficeController::class, 'index']);
  });

  // Role Route
  // resources: /admin/roles
  Route::prefix('/roles')->group(function () {
    Route::get('/', [AdminRoleController::class, 'getAllRoles']);
    Route::post('/', [AdminRoleController::class, 'addNewRole']);
  });

  // User Route
  // resources: /admin/users
  Route::prefix('/users')->group(function () {

    // Dean
    // resources: /admin/users/deans
    Route::prefix('/deans')->group(callback: function () {
      Route::put('/{dean_id}', [AdminDeanController::class, 'updateDeanUserById']);
      Route::get('/', [AdminDeanController::class, 'getAllDeans']);
      Route::get('/{dean_id}', [AdminDeanController::class, 'show']);
      Route::post('/', [AdminDeanController::class, 'addNewDeanUser']);
      Route::post('/archive', [AdminDeanController::class, 'archive']);
      Route::delete('/{dean_id}', [AdminDeanController::class, 'destroy']);
    });

    // Student
    // resources: /admin/users/students
    Route::prefix('/students')->group(function () {
      Route::get('/', [AdminStudentController::class, 'index']);
      Route::get('/{student_id}', [AdminStudentController::class, 'getStudent']);
      // TODO: DO THIS
      Route::post('/', [AdminStudentController::class, 'store']);
    });

    // Coordinator
    // resources: /admin/users/coordinators
    Route::prefix('/coordinators')->group(function () {
      Route::post('/', [AdminCoordinatorController::class, 'addNewCoordinator']);
    });

    // Company
    // resources: /admin/users/companies
    Route::prefix('/companies')->group(function (): void {

      Route::prefix('/{company_id}/offices')->group(function () {
        Route::delete('/{office_id}', [AdminCompanyOfficeController::class, 'deleteOfficeById']);
        Route::put('/{office_id}', [AdminCompanyOfficeController::class, 'updateOfficeById']);
        Route::get('/', [AdminCompanyOfficeController::class, 'getAllOffices']);
        Route::post('/', [AdminCompanyOfficeController::class, 'addNewOffice']);
        
      });
 
      Route::post('/archive/selected', [AdminCompanyController::class, 'archiveCompanies']);
      Route::delete('/archive/{company_id}', [AdminCompanyController::class, 'archive']);
      // Route to handle the CSV/Excel file upload and data import
      // Route::post('/import', [AdminCompanyController::class, 'importCompanies']);
      Route::get('/', [AdminCompanyController::class, 'index']);
      Route::get('/{company_id}', [AdminCompanyController::class, 'show']);
      Route::put('/{company_id}', [AdminCompanyController::class, 'updateCompanyById']);
      Route::post('/', [AdminCompanyController::class, 'addNewCompany']);
      Route::post('/archive', [AdminCompanyController::class, 'archiveSelectedCompany']);
      Route::delete('/{company_id}', [AdminCompanyController::class, 'destroy']);

      // ! TODO
      Route::post('/upload', [AdminCompanyController::class, 'upload']);
    });

    // Chairperson
    Route::prefix('/chairpersons')->group(function () {
      Route::delete('/archive/{chairperson_id}', [AdminChairpersonController::class, 'archive']);
      Route::get('/', [AdminChairpersonController::class, 'index']);
      Route::post('/', [AdminChairpersonController::class, 'store']);
      Route::put('/{chairperson_id}', [AdminChairpersonController::class, 'update']);
    });

    // Supervisors  
    Route::prefix('/supervisors')->group(function () {
      Route::get('/', [AdminSupervisorController::class, 'getAllSupervisors']);
      Route::get('/with-offices', [AdminSupervisorController::class, 'withOfficesAndCompany']);
    });

    // resources: /admin/users
    Route::post('/archive/selected', [AdminUserController::class, 'archiveUsers']);
    Route::delete('/archive/{user_id}', [AdminUserController::class, 'archive']);
    Route::get('/', [AdminUserController::class, 'index']);
    Route::post('/', [AdminUserController::class, 'store']);
    Route::put('/{user_id}', [AdminUserController::class, 'update']);
  });
  
  // Document Types
  Route::prefix('/document-types')->group(function () {
    Route::get('/', [AdminDocumentTypeController::class, 'getAllDocumentTypes']);
    Route::post('/', [AdminDocumentTypeController::class, 'addNewDocumentType']);
    Route::put('/{document_type_id}', [AdminDocumentTypeController::class, 'updateDocumentTypeById']);
    Route::delete('/{document_type_id}', [AdminDocumentTypeController::class, 'deleteDocumentTypeById']);
  });

  // Storage
  // resources: /admin/storage
  Route::prefix('/storage')->group(function () {

    // Folders
    // resources: /admin/storage/folders
    Route::prefix('/folders')->group(function () {
      Route::post('/', [AdminFolderController::class, 'addNewFolder']);
    });

  });
});
