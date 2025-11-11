<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Coordinator\CoordinatorSummaryController;
use App\Http\Controllers\Api\Chairperson\ChairpersonSummaryController;
use App\Http\Controllers\Api\OpenAI\ChairpersonOpenAISummaryController;
use App\Http\Controllers\Api\OpenAI\CoordinatorOpenAISummaryController;

Route::prefix('/summary')->group(function () {
    // Quick ping route to verify mounting/path
    Route::get('/ping', function () {
        return response()->json(['ok' => true, 'path' => '/api/v1/summary/ping']);
    });

    // Coordinator-focused and generic summary
    Route::post('/', [CoordinatorSummaryController::class, 'generate'])->middleware('role:chairperson,coordinator');
    Route::options('/', [CoordinatorSummaryController::class, 'options']);

    // Chairperson-specific summary path
    Route::post('/chair', [ChairpersonSummaryController::class, 'generate'])->middleware('role:chairperson');
    Route::get('/chair', [ChairpersonSummaryController::class, 'generate'])->middleware('role:chairperson');
    Route::options('/chair', [ChairpersonSummaryController::class, 'options']);
    
    // Chairperson OpenAI summarization endpoint
    Route::post('/openai-summarize', [ChairpersonOpenAISummaryController::class, 'summarize'])->middleware('role:chairperson');
    Route::get('/openai-summarize', [ChairpersonOpenAISummaryController::class, 'summarize'])->middleware('role:chairperson');
    Route::get('/openai-test', [ChairpersonOpenAISummaryController::class, 'test'])->middleware('role:chairperson');
    // Coordinator-specific OpenAI summarization endpoint
    Route::post('/openai-summarize-coordinator', [CoordinatorOpenAISummaryController::class, 'summarize'])->middleware('role:coordinator');
    Route::get('/openai-summarize-coordinator', [CoordinatorOpenAISummaryController::class, 'summarize'])->middleware('role:coordinator');
    Route::options('/openai-summarize', [CoordinatorSummaryController::class, 'options']);
});


