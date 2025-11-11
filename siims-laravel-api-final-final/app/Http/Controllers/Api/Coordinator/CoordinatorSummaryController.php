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
        // Increase execution time limit for large datasets, especially for "overall" week
        set_time_limit(120); // 2 minutes for processing large datasets
        
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

        // OPTIMIZATION: For "overall" week, use smarter query strategy
        if ($isOverall || $week === null || $week === 0) {
            // "Overall" case: Get all entries but limit to prevent timeout
            $maxEntries = 2000; // Higher limit for overall to get better coverage
            $rows = $query
                ->whereNotNull('we.tasks')
                ->whereNotNull('we.learnings')
                ->orderBy('we.week_number', 'asc') // Order by week first
                ->orderBy('we.created_at', 'desc') // Then by most recent
                ->limit($maxEntries)
                ->get();
        } else {
            // Specific week: Use moderate limit
            $maxEntries = 1000;
            $rows = $query
                ->where('we.week_number', $week)
                ->whereNotNull('we.tasks')
                ->whereNotNull('we.learnings')
                ->orderBy('we.created_at', 'desc')
                ->limit($maxEntries)
                ->get();
        }

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

        // OPTIMIZATION: For large datasets, intelligently limit data sent to OpenAI
        // Reduced limits for faster OpenAI processing
        // For "overall" week, use moderate limits to speed up processing
        // For specific weeks, use lower limits for even faster responses
        if ($isOverall || $week === null || $week === 0) {
            // "Overall" week: Reduced limits for faster processing
            $maxActivities = 60; // Reduced from 100 for faster processing
            $maxLearnings = 60;  // Reduced from 100 for faster processing
        } else {
            // Specific week: Lower limits for fastest processing
            $maxActivities = 30; // Reduced from 50 for faster processing
            $maxLearnings = 30; // Reduced from 50 for faster processing
        }
        
        // Use smart sampling: take evenly distributed samples, not just first N
        $limitedActivities = $this->smartSample($activities, $maxActivities);
        $limitedLearnings = $this->smartSample($learnings, $maxLearnings);
        
        if (count($activities) > $maxActivities || count($learnings) > $maxLearnings) {
            Log::info('CoordinatorSummary: Limiting data for OpenAI', [
                'week_type' => ($isOverall || $week === null || $week === 0) ? 'overall' : 'specific',
                'original_activities' => count($activities),
                'limited_activities' => count($limitedActivities),
                'original_learnings' => count($learnings),
                'limited_learnings' => count($limitedLearnings),
            ]);
        }
        
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
                            $limitedActivities, // Use limited data for OpenAI
                            $limitedLearnings   // Use limited data for OpenAI
                        );
                    }
                } else {
                    // Chairperson can access any student
                    $poAnalysisResult = $this->coordinatorSummaryService->generateSummaryWithPOAnalysis(
                        $combined, 
                        $week, 
                        $limitedActivities, // Use limited data for OpenAI
                        $limitedLearnings   // Use limited data for OpenAI
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

        // Use recommendations directly from OpenAI (no hard-coded fallbacks)
        $recommendations = $poAnalysisResult['recommendations'] ?? [];
        
        // Validate that OpenAI generated recommendations (log warning if missing, but don't add defaults)
        if (!empty($poAnalysisResult['pos_not_hit'])) {
            $notHitPOs = array_map(function($item) {
                return is_string($item) ? $item : ($item['po'] ?? '');
            }, $poAnalysisResult['pos_not_hit']);
            $notHitPOs = array_filter($notHitPOs, function($po) {
                return !empty($po) && preg_match('/^PO\d+$/', $po);
            });
            
            // Check if OpenAI generated enough recommendations
            $recommendedPOs = [];
            foreach ($recommendations as $rec) {
                if (is_string($rec) && preg_match_all('/PO\d+/', $rec, $matches)) {
                    foreach ($matches[0] as $po) {
                        $recommendedPOs[$po] = true;
                    }
                }
            }
            $missingPOs = array_diff($notHitPOs, array_keys($recommendedPOs));
            if (!empty($missingPOs)) {
                Log::warning('Coordinator: OpenAI did not generate recommendations for all not met POs', [
                    'missing_pos' => $missingPOs,
                    'total_not_hit' => count($notHitPOs),
                    'recommendations_count' => count($recommendations)
                ]);
            }
        }

        // Merge summary and PO analysis results
        // Include both corrected and original activities/learnings for frontend optimization
        // Always return FULL activities/learnings to frontend (not the limited ones sent to OpenAI)
        // This ensures the frontend gets all the data even though OpenAI only processed a subset
        $correctedActivities = $poAnalysisResult['corrected_activities'] ?? $activities;
        $correctedLearnings = $poAnalysisResult['corrected_learnings'] ?? $learnings;
        
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
            'recommendations' => $recommendations, // Use enhanced recommendations
            // Include activities and learnings for frontend optimization (avoids re-fetching)
            // Always use FULL data, not the limited data sent to OpenAI
            'activities' => $correctedActivities, // Use corrected if available, otherwise original (FULL data)
            'learnings' => $correctedLearnings,   // Use corrected if available, otherwise original (FULL data)
            'corrected_activities' => $correctedActivities, // Backward compatibility
            'corrected_learnings' => $correctedLearnings,   // Backward compatibility
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    /**
     * Smart sampling: evenly distribute samples across the array
     * This ensures we get representative data from all parts of the dataset
     * 
     * @param array $data
     * @param int $maxItems
     * @return array
     */
    private function smartSample(array $data, int $maxItems): array
    {
        $count = count($data);
        if ($count <= $maxItems) {
            return $data;
        }
        
        // If we have more items than max, sample evenly across the array
        $step = $count / $maxItems;
        $sampled = [];
        
        for ($i = 0; $i < $maxItems; $i++) {
            $index = (int) round($i * $step);
            if ($index < $count) {
                $sampled[] = $data[$index];
            }
        }
        
        return $sampled;
    }
}

