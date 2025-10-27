<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SummaryController;
use App\Http\Controllers\Api\ChairSummaryController;
use App\Http\Controllers\Api\OpenAISummaryController;
use App\Http\Controllers\Api\CoordinatorOpenAISummaryController;

Route::prefix('/summary')->group(function () {
    // Quick ping route to verify mounting/path
    Route::get('/ping', function () {
        return response()->json(['ok' => true, 'path' => '/api/v1/summary/ping']);
    });

    // Coordinator-focused and generic summary
    Route::post('/', [SummaryController::class, 'generate'])->middleware('role:chairperson,coordinator');
    Route::options('/', [SummaryController::class, 'options']);

    // Chairperson-specific summary path
    Route::post('/chair', [ChairSummaryController::class, 'generate'])->middleware('role:chairperson');
    Route::get('/chair', [ChairSummaryController::class, 'generate'])->middleware('role:chairperson');
    Route::options('/chair', [SummaryController::class, 'options']);
    
    // Chair OpenAI summarization endpoint
    Route::post('/openai-summarize', [OpenAISummaryController::class, 'summarize'])->middleware('role:chairperson');
    Route::get('/openai-test', [OpenAISummaryController::class, 'test'])->middleware('role:chairperson');
    // Coordinator-specific OpenAI summarization endpoint
    Route::post('/openai-summarize-coordinator', [CoordinatorOpenAISummaryController::class, 'summarize'])->middleware('role:coordinator');
    Route::get('/openai-summarize-coordinator', [CoordinatorOpenAISummaryController::class, 'summarize'])->middleware('role:coordinator');
    Route::options('/openai-summarize', [SummaryController::class, 'options']);
});


