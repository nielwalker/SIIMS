<?php

namespace App\Http\Controllers\Api\OpenAI;

use App\Http\Controllers\Controller;
use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\CoordinatorPromptBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Summary Controller for Coordinator
 * 
 * Handles OpenAI summarization requests for coordinator role.
 * Moved from App\Http\Controllers\Api\CoordinatorOpenAISummaryController
 */
class CoordinatorSummaryController extends Controller
{
    protected $openAIService;
    protected $promptBuilder;

    public function __construct(OpenAIService $openAIService, CoordinatorPromptBuilder $promptBuilder)
    {
        $this->openAIService = $openAIService;
        $this->promptBuilder = $promptBuilder;
    }

    /**
     * Generate summary using OpenAI for coordinator
     */
    public function summarize(Request $request)
    {
        try {
            $data = $request->input('data');
            if (is_string($data)) {
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $decoded;
                }
            }
            
            // Support AI weekly-task suggestions: type=chair_weekly_tasks_json
            $type = $request->input('type') ?: ($data['type'] ?? null);
            if ($type === 'chair_weekly_tasks_json' && isset($data['coordinators'])) {
                return $this->handleWeeklyTasksRequest($data['coordinators']);
            }
            
            // Also support GET with base64/json in query param
            if (!$data && $request->query('data')) {
                $raw = $request->query('data');
                $raw = is_string($raw) ? urldecode($raw) : $raw;
                $tryJson = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $tryJson;
                } else {
                    $tryJson = json_decode(base64_decode($raw), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data = $tryJson;
                    }
                }
            }
            
            if (!$data) {
                return response()->json(['error' => 'No data provided'], 400);
            }

            $activities = $data['corrected_activities'] ?? [];
            $learnings = $data['corrected_learnings'] ?? [];
            $assessment = $data['summary for this section on a week'] ?? '';

            if (is_string($activities)) { 
                $activities = json_decode($activities, true) ?? []; 
            }
            if (is_string($learnings)) { 
                $learnings = json_decode($learnings, true) ?? []; 
            }

            $activities = $this->openAIService->cleanDataArray($activities);
            $learnings = $this->openAIService->cleanDataArray($learnings);
            $assessment = $this->openAIService->cleanText($assessment);

            // Build prompt using CoordinatorPromptBuilder
            $prompt = $this->promptBuilder->buildPrompt($activities, $learnings, $assessment);

            $response = $this->openAIService->callSimple($prompt, 'gpt-3.5-turbo', 300, 0.6, 30);
            
            if ($response['success']) {
                $clean = $this->openAIService->cleanText($response['summary']);
                // Ensure intro
                if (!preg_match('/^For\s+this\s+week,\s+the\s+student/i', $clean)) {
                    $clean = 'For this week, the student ' . ltrim($clean);
                }
                return response()->json(['summary' => $clean, 'success' => true]);
            }

            // OpenAI not available - return error
            Log::warning('OpenAI failed for coordinator summary', ['error' => $response['error'] ?? 'Unknown error']);
            return response()->json([
                'error' => 'OpenAI is not available right now',
                'message' => $response['error'] ?? 'Unable to generate summary',
                'openai_unavailable' => true
            ], 503);

        } catch (\Throwable $e) {
            Log::error('Coordinator OpenAI Summarization Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle weekly tasks JSON request
     */
    private function handleWeeklyTasksRequest(array $coordinators): \Illuminate\Http\JsonResponse
    {
        $brief = [];
        foreach ($coordinators as $c) {
            $brief[] = [
                'id' => (string)($c['id'] ?? ''),
                'label' => (string)($c['label'] ?? ''),
                'gaps' => array_values(array_unique(array_filter((array)($c['gaps'] ?? []))))
            ];
        }
        
        $instruction = "You are an expert internship coach. For each coordinator below, generate 3-5 concrete weekly task suggestions to address their gap POs (POs with zero). Tasks must be short and actionable and MUST include the PO code (e.g., PO2). Return STRICT JSON ONLY in the exact shape {\"tasksPerCoordinator\":{\"<id>\":[\"task1\",\"task2\"]}} with no extra prose.";
        $prompt = $instruction."\n\nCOORDINATORS:\n".json_encode($brief, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        
        $resp = $this->openAIService->callSimple($prompt);
        if ($resp['success']) {
            $content = $resp['summary'];
            $parsed = json_decode($content, true);
            if (!is_array($parsed) && preg_match('/\{[\s\S]*\}/', $content, $m)) {
                $parsed = json_decode($m[0], true);
            }
            if (is_array($parsed) && isset($parsed['tasksPerCoordinator']) && is_array($parsed['tasksPerCoordinator'])) {
                return response()->json(['tasksPerCoordinator' => $parsed['tasksPerCoordinator']], 200);
            }
            return response()->json(['error' => 'AI returned unexpected format'], 502);
        }
        return response()->json(['error' => 'AI request failed'], 502);
    }
}

