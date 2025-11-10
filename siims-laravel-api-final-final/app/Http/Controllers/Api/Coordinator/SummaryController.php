<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\Coordinator\CoordinatorSummaryAdapter;
use App\Services\Coordinator\CoordinatorSummaryService;
use App\Services\OpenAI\SummaryEvaluationService;
use Illuminate\Support\Facades\Log;

class SummaryController extends Controller
{
    protected $evaluationService;
    protected $coordinatorSummaryService;

    public function __construct(
        SummaryEvaluationService $evaluationService,
        CoordinatorSummaryService $coordinatorSummaryService
    ) {
        $this->evaluationService = $evaluationService;
        $this->coordinatorSummaryService = $coordinatorSummaryService;
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
        $activities = [];
        $learnings = [];
        
        foreach ($rows as $row) {
            if (!empty($row->tasks)) {
                $cleanTasks = strip_tags($row->tasks);
                $cleanTasks = preg_replace('/\s+/', ' ', $cleanTasks);
                $cleanTasks = trim($cleanTasks);
                if (!empty($cleanTasks)) {
                    $activities[] = $cleanTasks;
                }
            }
            if (!empty($row->learnings)) {
                $cleanLearnings = strip_tags($row->learnings);
                $cleanLearnings = preg_replace('/\s+/', ' ', $cleanLearnings);
                $cleanLearnings = trim($cleanLearnings);
                if (!empty($cleanLearnings)) {
                    $learnings[] = $cleanLearnings;
                }
            }
        }
        
        // Remove duplicates
        $activities = array_values(array_unique(array_filter($activities)));
        $learnings = array_values(array_unique(array_filter($learnings)));

        // Build combined text: include both activities and learnings for summary generation
        $combined = $rows->map(function ($r) {
            $t = trim(($r->tasks ?? '') . ' ' . ($r->learnings ?? ''));
            $t = preg_replace('/\s+/', ' ', $t);
            if ($t && !preg_match('/[.!?]$/', $t)) {
                $t .= '.';
            }
            return $t;
        })->filter()->implode(' ');

        // Convert to third-person for consistency
        $combined = $this->convertToThirdPerson($combined);

        $keywordSets = [
            ['math', 'mathematics', 'science', 'algorithm', 'compute', 'analysis'],
            ['best practice', 'standard', 'policy', 'method', 'procedure', 'protocol'],
            ['analyze', 'analysis', 'problem', 'root cause', 'diagnose', 'troubleshoot'],
            ['user need', 'requirement', 'stakeholder', 'ux', 'usability'],
            ['design', 'implement', 'evaluate', 'build', 'develop', 'test', 'setup', 'configure', 'configuration', 'install'],
            ['safety', 'health', 'environment', 'security', 'ethical'],
            ['tool', 'framework', 'library', 'technology', 'platform'],
            ['team', 'collaborat', 'leader', 'group'],
            ['plan', 'schedule', 'timeline', 'project plan'],
            ['communicat', 'present', 'documentation', 'write', 'report'],
            ['impact', 'society', 'organization', 'community'],
            ['ethical', 'privacy', 'legal', 'compliance'],
            ['learn', 'self-study', 'latest', 'new skill'],
            ['research', 'experiment', 'study', 'investigation'],
            ['filipino', 'heritage', 'culture', 'tradition'],
        ];

        $lower = mb_strtolower($combined);
        // 1) Word/keyword matching (bag-of-words, stems & variants)
        $counts = array_map(function ($set) use ($lower) {
            $count = 0;
            foreach ($set as $kw) {
                if (str_contains($lower, $kw)) { $count++; continue; }
                $words = explode(' ', $kw);
                if (count($words) > 1) {
                    foreach ($words as $w) {
                        if (str_contains($lower, $w)) { $count++; continue 2; }
                    }
                }
                $stem = preg_replace('/(ing|ed|es|s)$/', '', $kw);
                if (strlen($stem) > 3 && str_contains($lower, $stem)) { $count++; continue; }
                foreach ([$kw.'s', $kw.'ing', $kw.'ed', preg_replace('/s$/', '', $kw)] as $v) {
                    if (str_contains($lower, $v)) { $count++; break; }
                }
            }
            return $count;
        }, $keywordSets);
        $total = array_sum($counts) ?: 1;
        $keywordScores = array_map(function ($c) use ($total) {
            return (int) round(($c / $total) * 100);
        }, $counts);

        // Note: Context-based matching is only done by OpenAI, not here
        // This controller only does word-based text mining (keyword matching)

        // Pass week number to adapter for OpenAI summarization
        // OpenAI service will format the summary with proper week prefix
        $summaryResult = $adapter->analyze($combined, $analysisType, $useGPT, $week);
        $summary = $summaryResult['summary'];

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
                'keywordScores' => $keywordScores,
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
                $summary = $this->enforceWeekPrefix($summary);
            }
        }

        // EVALUATION: Compare summary against raw database data
        // Build reference text from raw activities and learnings
        $referenceText = $this->buildReferenceText($rows, $analysisType);
        
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
            'keywordScores' => $keywordScores, // Word-based text mining scores only
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
    
    /**
     * Build reference text from raw database data for evaluation
     * 
     * @param \Illuminate\Support\Collection $rows Raw rows from database
     * @param string|null $analysisType 'coordinator' or 'chairman'
     * @return string Combined reference text
     */
    private function buildReferenceText($rows, ?string $analysisType): string
    {
        $parts = [];
        
        foreach ($rows as $row) {
            // Include both activities and learnings for all analysis types
            if (!empty($row->tasks)) {
                $parts[] = 'Activities: ' . trim($row->tasks);
            }
            if (!empty($row->learnings)) {
                $parts[] = 'Learnings: ' . trim($row->learnings);
            }
        }
        
        return implode(' ', $parts);
    }

    private function enforceWeekPrefix(string $text): string
    {
        $t = trim($text);
        if ($t === '') {
            return '';
        }
        if (preg_match('/^For\s+this\s+week,\s+the\s+student/i', $t)) {
            return $t;
        }
        $t = preg_replace('/^(In\s+week\s+\d+\s*,\s*|This\s+week\s*,\s*|In\s+this\s+week\s*,\s*)/i', '', $t);
        return 'For this week, the student ' . ltrim($t);
    }
    
    private function convertToThirdPerson($text)
    {
        if (!is_string($text) || $text === '') return $text;

        $replacements = [
            // First-person singular contractions and phrases
            '/\bI\'m\b/i' => 'the student is',
            '/\bI\'ve\b/i' => 'the student has',
            '/\bI\'d\b/i' => 'the student would',
            '/\bI\'ll\b/i' => 'the student will',
            '/\bI was able to\b/i' => 'the student was able to',
            '/\bI was\b/i' => 'the student was',
            '/\bI am\b/i' => 'the student is',
            '/\bI have\b/i' => 'the student has',
            '/\bI had\b/i' => 'the student had',
            '/\bI can\b/i' => 'the student can',
            '/\bI could\b/i' => 'the student could',
            '/\bI learned\b/i' => 'the student learned',
            '/\bI became\b/i' => 'the student became',
            '/\bI gained\b/i' => 'the student gained',
            '/\bI developed\b/i' => 'the student developed',
            '/\bI acquired\b/i' => 'the student acquired',
            '/\bI improved\b/i' => 'the student improved',
            '/\bI enhanced\b/i' => 'the student enhanced',
            '/\bI\b/i' => 'the student',
            '/\bme\b/i' => 'the student',
            '/\bmyself\b/i' => 'themselves',
            '/\bmy\b/i' => 'the student\'s',

            // First-person plural
            '/\bwe\'re\b/i' => 'the students are',
            '/\bwe\'ve\b/i' => 'the students have',
            '/\bwe\'d\b/i' => 'the students would',
            '/\bwe\'ll\b/i' => 'the students will',
            '/\bwe were able to\b/i' => 'the students were able to',
            '/\bwe were\b/i' => 'the students were',
            '/\bwe are\b/i' => 'the students are',
            '/\bwe have\b/i' => 'the students have',
            '/\bwe had\b/i' => 'the students had',
            '/\bwe can\b/i' => 'the students can',
            '/\bwe could\b/i' => 'the students could',
            '/\bwe learned\b/i' => 'the students learned',
            '/\bwe\b/i' => 'the student',
            '/\bus\b/i' => 'the student',
            '/\bour\b/i' => 'the student\'s',
            '/\bours\b/i' => 'the student\'s',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }
}

