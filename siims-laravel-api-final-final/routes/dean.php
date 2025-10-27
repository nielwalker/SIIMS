<?php

// Dean Routes
// resources: /dean

use App\Http\Controllers\DeanCompanyController;
use App\Http\Controllers\DeanController;
use App\Http\Controllers\DeanProgramController;
use Illuminate\Support\Facades\Route;

Route::prefix('/dean')->middleware('role:dean')->group(function () {

  // Dashboard 
  Route::get('/dashboard', [DeanController::class, 'dashboard']);
  // Profile
  Route::get('/profile', [DeanController::class, 'profile']);

  // Program Routes
  // resources: /dean/programs
  Route::prefix('programs')->group(function () {
    Route::get('/', [DeanProgramController::class, 'getAllPrograms']);
    Route::post('/', [DeanProgramController::class, 'addNewProgram']);
    Route::put('/{program_id}', [DeanProgramController::class, 'updateProgramById']);
  });

  // Company Routes
  // resources: /dean/companies
  Route::prefix('/companies')->group(function () {

    // Offices Route
    // resources: /dean/company_id/offices
    Route::prefix('/{company_id}/offices')->group(function () {
      Route::get('/', [DeanCompanyController::class, 'getAllOffices']);
    });

    Route::get('/', [DeanCompanyController::class, 'index']);
    Route::get('/{company_id}', [DeanCompanyController::class, 'show']);
    Route::post('/', [DeanCompanyController::class, 'store']);
  });
});
