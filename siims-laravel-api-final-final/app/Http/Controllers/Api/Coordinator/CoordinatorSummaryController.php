<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\Coordinator\CoordinatorSummaryAdapter;
use App\Services\Coordinator\CoordinatorSummaryService;
use App\Services\OpenAI\SummaryEvaluationService;
use App\Services\OpenAI\Traits\TextProcessingTrait;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\Facades\Log;

class CoordinatorSummaryController extends Controller
{
    use TextProcessingTrait;

    protected $evaluationService;
    protected $coordinatorSummaryService;
    protected $openAIService;

    public function __construct(
        SummaryEvaluationService $evaluationService,
        CoordinatorSummaryService $coordinatorSummaryService,
        OpenAIService $openAIService
    ) {
        $this->evaluationService = $evaluationService;
        $this->coordinatorSummaryService = $coordinatorSummaryService;
        $this->openAIService = $openAIService;
    }

    public function options(): JsonResponse
    {
        return response()->json(null, 204, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    public function generate(Request $request, CoordinatorSummaryAdapter $adapter): JsonResponse
    {
        $section = $request->input('section');
        $studentId = $request->input('studentId');
        $coordinatorId = $request->input('coordinatorId');
        $week = $request->integer('week');
        $useGPT = (bool) $request->input('useGPT');
        $analysisType = $request->input('analysisType');
        $isOverall = $request->boolean('isOverall');

        // Fetch reports: basic example using weekly_entries and students tables
        // Adjust table/column names to match your schema
        $query = DB::table('weekly_entries as we')
            ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
            ->join('students as s', 's.id', '=', 'we.student_id');

        if ($studentId) {
            $query->where('we.student_id', $studentId);
        }

        if ($coordinatorId) {
            // assuming students table has coordinator_id
            $query->where('s.coordinator_id', $coordinatorId);
        }

        if ($section) {
            // assuming students table has section or section_id
            $query->where(function ($q) use ($section) {
                $q->where('s.section', $section)
                  ->orWhere('s.section_id', $section);
            });
        }

        $rows = $query->when(!$isOverall && $week, function ($q) use ($week) {
                $q->where('we.week_number', $week);
            })
            ->get();

        // Extract activities/tasks and learnings separately for PO analysis
        $extracted = $this->extractActivitiesAndLearnings($rows);
        $activities = $extracted['activities'];
        $learnings = $extracted['learnings'];

        // Build combined text: include both activities and learnings for summary generation
        $combined = $this->buildCombinedText($rows);

        // Convert to third-person for consistency
        $combined = $this->convertToThirdPerson($combined);

        // Pass week number to adapter for OpenAI summarization
        // The adapter handles both summary generation AND keyword matching (text mining)
        // OpenAI service will format the summary with proper week prefix
        $summaryResult = $adapter->analyze($combined, $analysisType, $useGPT, $week);
        $summary = $summaryResult['summary'];
        $keywordScores = $summaryResult['keywordScores'] ?? [];

        // Generate PO analysis using CoordinatorSummaryService
        $poAnalysisResult = [];
        if ($useGPT && !empty($combined) && $studentId) {
            // Verify coordinator has access to this student
            $user = auth()->user();
            if ($user && ($user->hasRole('coordinator') || $user->hasRole('chairperson'))) {
                if ($user->hasRole('coordinator')) {
                    $student = DB::table('students')->where('id', $studentId)->first();
                    if ($student && $student->coordinator_id == $user->id) {
                        $poAnalysisResult = $this->coordinatorSummaryService->generateSummaryWithPOAnalysis(
                            $combined, 
                            $week, 
                            $activities, 
                            $learnings
                        );
                    }
                } else {
                    // Chairperson can access any student
                    $poAnalysisResult = $this->coordinatorSummaryService->generateSummaryWithPOAnalysis(
                        $combined, 
                        $week, 
                        $activities, 
                        $learnings
                    );
                }
            }
        }

        // Check if OpenAI was unavailable for PO analysis
        if (isset($poAnalysisResult['openai_unavailable']) && $poAnalysisResult['openai_unavailable']) {
            return response()->json([
                'summary' => $summary,
                'keywordScores' => $keywordScores, // From adapter (word-based text mining)
                'usedGPT' => (bool) $summaryResult['usedGPT'],
                'error' => 'PO Analysis is currently unavailable',
                'openai_unavailable' => true,
                'pos_hit' => [],
                'pos_not_hit' => [],
                'po_word_hit' => [],
                'po_context_hit' => [],
                'recommendations' => [],
            ], 503, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        }

        // Ensure summary is third-person and has proper week prefix
        if (!empty($summary)) {
            $summary = $this->convertToThirdPerson($summary);
            if (!empty($week)) {
                $summary = $this->openAIService->enforceWeekPrefix($summary, 'For this week, the student ');
            }
        }

        // EVALUATION: Compare summary against raw database data
        // Build reference text from raw activities and learnings
        $referenceText = $this->buildReferenceText($activities, $learnings);
        
        // Only evaluate if we have both summary and reference text
        $evaluationResults = null;
        if (!empty($summary) && !empty($referenceText)) {
            try {
                Log::info('Starting summary evaluation', [
                    'analysis_type' => $analysisType,
                    'summary_length' => strlen($summary),
                    'reference_length' => strlen($referenceText)
                ]);
                
                $evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
                
                // Log evaluation results to console and Laravel log
                $context = $analysisType === 'coordinator' ? 'Coordinator Summary' : 'Chairperson Summary';
                $this->evaluationService->logResults($evaluationResults, $context);
                
                Log::info('Summary evaluation completed', [
                    'rouge1_f1' => $evaluationResults['rouge1']['f1'],
                    'rouge2_f1' => $evaluationResults['rouge2']['f1'],
                    'rougeL_f1' => $evaluationResults['rougeL']['f1'],
                    'bertScore' => $evaluationResults['bertScore']
                ]);
            } catch (\Throwable $e) {
                Log::error('Summary evaluation error', ['error' => $e->getMessage()]);
            }
        }

        // Merge summary and PO analysis results
        return response()->json([
            'summary' => $summary,
            'keywordScores' => $keywordScores, // From adapter (word-based text mining)
            'usedGPT' => (bool) $summaryResult['usedGPT'],
            'evaluation' => $evaluationResults, // Include evaluation in response for debugging
            // PO Analysis data
            'pos_hit' => $poAnalysisResult['pos_hit'] ?? [],
            'pos_not_hit' => $poAnalysisResult['pos_not_hit'] ?? [],
            'po_word_hit' => $poAnalysisResult['po_word_hit'] ?? [],
            'po_context_hit' => $poAnalysisResult['po_context_hit'] ?? [],
            'recommendations' => $poAnalysisResult['recommendations'] ?? [],
            'corrected_activities' => $poAnalysisResult['corrected_activities'] ?? $activities,
            'corrected_learnings' => $poAnalysisResult['corrected_learnings'] ?? $learnings,
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }
    
}

