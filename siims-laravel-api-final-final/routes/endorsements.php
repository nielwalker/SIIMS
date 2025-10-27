<?php

use App\Http\Controllers\EndorsementController;
use Illuminate\Support\Facades\Route;

Route::prefix('/endorsements')->middleware('role:student')->group(function () {
    // GET
    Route::get('/{work_post_id}', [EndorsementController::class, 'findEndorsementByWorkPostID']);
    Route::get('/', [EndorsementController::class, 'getAllEndorsements']);
}); 