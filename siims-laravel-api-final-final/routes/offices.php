<?php

use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\OfficeV2Controller;
use Illuminate\Support\Facades\Route;

Route::prefix('/offices')->group(function () {
    
    /**
     * GET
     */
    Route::get('/lists', [OfficeController::class ,'getAllListsOfOffices'])->middleware('role:admin,company');
    
    /**
     * Admin, Company
     */
    Route::get('/archives', [OfficeController::class, 'getAllOfficesArchives'])->middleware('role:admin,company');
    Route::get('/', [OfficeController::class, 'getAllOffices'])->middleware('role:admin,company');

    /**
     * DELETE
     */
    Route::delete('/{office_id}', [OfficeController::class, 'deleteOfficeById'])->middleware('role:admin,company');

}); 

Route::prefix('/v2/offices')->group(function () {

    /**
     * GET
     */
    Route::get('/get-company-offices', [OfficeV2Controller::class, 'getAllCompanyOffices']);

});