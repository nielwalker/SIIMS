<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyV2Controller;
use App\Http\Controllers\ProgramController;
use Illuminate\Support\Facades\Route;


Route::prefix('/v2/companies')->middleware('role:admin,dean,chairperson,coordinator')->group(function () {

  /**
   * GET
   */
  Route::get('/search', [CompanyV2Controller::class, 'searchCompany']);
  Route::get('/', [CompanyV2Controller::class, 'getAllCompanies']);

  // PUT
  Route::put('/{company_id}/restore', [CompanyV2Controller::class, 'restoreCompanyByID']);
  
  // DELETE
  Route::delete('/{company_id}', [CompanyV2Controller::class, 'deleteCompanyByID'])->middleware('role:admin,dean,chairperson');
});


// Company Routes
// resources: /companies

Route::prefix('/companies')->middleware('role:admin,dean,chairperson')->group(function () {

  // Get 
  Route::get('/', [CompanyController::class, 'getAllCompanies']);

  // POST
  Route::post('/', [CompanyController::class, 'addNewCompany']);

  // PUT
  Route::put('/{company_id}', [CompanyController::class, 'updateCompanyById']);

  // DELETE
  Route::delete('/{company_id}', [CompanyController::class, 'deleteCompanyByID'])->middleware('role:admin');

});