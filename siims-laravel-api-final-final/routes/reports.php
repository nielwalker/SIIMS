<?php

use App\Http\Controllers\DtrController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('/reports')->middleware('role:admin,supervisor,company,student,coordinator')->group(function () {
    
    // Daily Time Record
    Route::prefix('/{application_id}/daily-time-records')->group(function () {
        /**
         * POST
         */
        // Route::post('/{application_id}', [DtrController::class, 'addNewDtr']);
        Route::post('/', [DtrController::class, 'addNewDtr']);

        /**
         * GET
         */
        Route::get('/latest', [DtrController::class, 'getLatestDtrEntries'])->middleware('role:student');
        Route::get('/', [DtrController::class, 'getAllTimeRecords'])->middleware('role:student');
        // Route::get('/{application_id}', [DtrController::class, 'getDtrByApplicationId']);
        

        /**
         * PUT 
         */
        Route::put('/{dtr_id}', [DtrController::class, 'updateDtrById']);
        Route::put('/{dtr_id}/mark-status', [DtrController::class, 'markDtrById'])->middleware('role:company,supervisor');

        /**
         * DELETE
         */
        Route::delete('/{dtr_id}', [DtrController::class, 'deleteDtrById']);
    });

    /**
     * Submit Daily Time Record
     */
    Route::post('/{application_id}/daily-time-record/submit', [ReportController::class, 'uploadTimeRecord'])->middleware('role:student');
    /**
     * Submit Weeky Accomplishment Report
     */
    Route::post('/{application_id}/weekly-report/submit', [ReportController::class, 'uploadWeeklyReport'])->middleware('role:student');
    /**
     * Submit Performance Evaluation
     */
    Route::post('/{application_id}/performance-evaluation/submit', [ReportController::class, 'uploadPerformanceEvaluation'])->middleware('role:admin,supervisor,company');
    /**
     * Submit Personal Insight Report
     */
    Route::post('/{application_id}/personal-insight/submit', [ReportController::class, 'uploadInsight'])->middleware('role:student');

    /**
     * GET
     */
    Route::get('/active', [ReportController::class, 'getAllActiveStudents'])->middleware('role:supervisor,company,coordinator');
    Route::get('/completed', [ReportController::class, 'getAllCompletedStudents'])->middleware('role:supervisor,company,coordinator');
}); 