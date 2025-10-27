<?php

use App\Http\Controllers\DocumentTypeV2Controller;
use App\Http\Controllers\DocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('/document-types')->middleware('role:admin,osa')->group(function () {
    // GET
    Route::get('/', [DocumentTypeController::class, 'getAllDocumentTypes']);

    // POST
    Route::post('/', [DocumentTypeController::class, 'addDocumentType']);

    // PUT
    Route::put('/{id}/restore', [DocumentTypeController::class, 'restoreDocumentTypeByID']);
    Route::put('/{document_type_id}', [DocumentTypeController::class, 'updateDocumentTypeID']);

    // DELETE
    Route::delete('/{document_type_id}', [DocumentTypeController::class, 'deleteDocumentTypeByID'])->middleware('role:admin');
});
