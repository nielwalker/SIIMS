<?php

use App\Http\Controllers\DocumentTypeV2Controller;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('/sections')->middleware('role:admin,coordinator,chairperson')->group(function () {
    // POST
    Route::post('/', [SectionController::class, 'create']);

    // GET
    Route::get('/students', [SectionController::class, 'getStudents']);

    Route::get('/', [SectionController::class, 'get']);

    // PUT
    Route::put('/{section_id}/assign', [SectionController::class, 'assignStudents']);
    Route::put('/{section_id}', [SectionController::class, 'update']);

    // DELETE
    Route::delete('/{section_id}', [SectionController::class, 'delete']);
});
