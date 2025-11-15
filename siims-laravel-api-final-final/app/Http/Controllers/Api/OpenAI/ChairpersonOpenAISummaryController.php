<?php

namespace App\Http\Controllers\Api\OpenAI;

use App\Http\Controllers\Controller;
use App\Services\OpenAI\OpenAIService;
use App\Services\Chairperson\ChairSummaryPromptBuilder;
use App\Services\OpenAI\SummaryEvaluationService;
use App\Services\OpenAI\Traits\TextProcessingTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Summary Controller for Chairperson
 * 
 * Handles OpenAI summarization requests for chairperson role.
 * Moved from App\Http\Controllers\Api\OpenAISummaryController
 */
class ChairpersonOpenAISummaryController extends Controller
{
    use TextProcessingTrait;

    protected $openAIService;
    protected $promptBuilder;
    protected $evaluationService;

    public function __construct(
        OpenAIService $openAIService,
        ChairSummaryPromptBuilder $promptBuilder,
        SummaryEvaluationService $evaluationService
    ) {
        $this->openAIService = $openAIService;
        $this->promptBuilder = $promptBuilder;
        $this->evaluationService = $evaluationService;
    }

    /**
     * Test endpoint to verify OpenAI configuration
     */
    public function test()
    {
        $user = auth()->user();
        return response()->json([
            'status' => 'OpenAI controller is working',
            'timestamp' => now(),
            'openai_configured' => $this->openAIService->isAvailable(),
            'user_authenticated' => !!$user,
            'user_id' => $user ? $user->id : null,
            'user_roles' => $user ? $user->roles->pluck('name')->toArray() : [],
            'user_email' => $user ? $user->email : null
        ]);
    }
    
    /**
     * Generate summary using OpenAI
     * Supports both POST (JSON body) and GET (query params) requests
     */
    public function summarize(Request $request)
    {
        try {
            // Handle both POST (JSON body) and GET (query params) requests
            $data = $request->input('data');
            $type = $request->input('type', 'overall_summary');
            
            // If data comes as query param (GET request), parse it
            if (!$data && $request->has('data')) {
                $dataParam = $request->query('data');
                if (is_string($dataParam)) {
                    $decoded = json_decode(urldecode($dataParam), true);
                    if (is_array($decoded)) {
                        $data = $decoded;
                    }
                }
            }
            
            Log::info('OpenAI Summarization Request', [
                'type' => $type,
                'data_keys' => array_keys($data ?? [])
            ]);
            
            if (!$data) {
                return response()->json(['error' => 'No data provided'], 400);
            }
            
            // Extract and clean structured data
            $activities = $data['corrected_activities'] ?? [];
            $learnings = $data['corrected_learnings'] ?? [];
            $assessment = $data['summary for this section on a week'] ?? '';
            
            // Normalize arrays if they're strings
            if (is_string($activities)) {
                $activities = json_decode($activities, true) ?? [];
            }
            if (is_string($learnings)) {
                $learnings = json_decode($learnings, true) ?? [];
            }
            
            // Clean up the data
            $activities = $this->openAIService->cleanDataArray($activities);
            $learnings = $this->openAIService->cleanDataArray($learnings);
            $assessment = $this->openAIService->cleanText($assessment);
            
            Log::info('Cleaned data for OpenAI', [
                'activities' => $activities,
                'learnings' => $learnings,
                'assessment' => $assessment
            ]);
            
            // Build prompt using PromptBuilder
            $prompt = $this->promptBuilder->buildSummaryPrompt($activities, $learnings, $assessment, $type);
            
            // Call OpenAI API
            $response = $this->openAIService->call($prompt, [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 3000,
                'temperature' => 0.2,
                'timeout' => 120,
            ]);
            
            if ($response['success'] && $response['content']) {
                // Clean the OpenAI response
                $cleanSummary = $this->openAIService->cleanText($response['content']);
                if ($type !== 'overall_summary') {
                    $cleanSummary = $this->openAIService->enforceWeekPrefix($cleanSummary, 'For this week, those students ');
                }
                
                Log::info('OpenAI generated summary', ['summary' => $cleanSummary]);
                
                // EVALUATION: Compare OpenAI summary against raw database data
                // Build reference text from raw activities and learnings (this is what we're comparing against)
                $referenceText = $this->buildReferenceText($activities, $learnings, $assessment);
                
                // Debug: Log that evaluation is starting
                Log::info('Starting summary evaluation', [
                    'summary_length' => strlen($cleanSummary),
                    'reference_length' => strlen($referenceText)
                ]);
                
                // Evaluate the generated summary against the reference text
                $evaluationResults = $this->evaluationService->evaluate($cleanSummary, $referenceText);
                
                // Log evaluation results to console and Laravel log
                $this->evaluationService->logResults($evaluationResults, 'Chairperson OpenAI Summary');
                
                // Debug: Confirm evaluation completed
                Log::info('Summary evaluation completed', [
                    'rouge1_f1' => $evaluationResults['rouge1']['f1'],
                    'rouge2_f1' => $evaluationResults['rouge2']['f1'],
                    'rougeL_f1' => $evaluationResults['rougeL']['f1'],
                    'bertScore' => $evaluationResults['bertScore']
                ]);
                
                return response()->json([
                    'summary' => $cleanSummary,
                    'success' => true,
                    'evaluation' => $evaluationResults // Include evaluation in response for debugging
                ]);
            } else {
                Log::warning('OpenAI failed', ['error' => $response['error'] ?? 'Unknown error']);
                
                return response()->json([
                    'error' => 'OpenAI is not available right now',
                    'message' => $response['error'] ?? 'Unable to generate summary',
                    'openai_unavailable' => true
                ], 503);
            }
            
        } catch (\Exception $e) {
            Log::error('OpenAI Summarization Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'OpenAI is not available right now',
                'message' => 'Unable to generate summary at this time',
                'openai_unavailable' => true
            ], 503);
        }
    }
    
}

