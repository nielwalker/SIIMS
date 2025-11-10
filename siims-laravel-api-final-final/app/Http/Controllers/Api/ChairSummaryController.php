<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ChairSummaryAdapter;
use App\Services\OpenAI\SummaryEvaluationService;

class ChairSummaryController extends Controller
{
    protected $evaluationService;

    public function __construct(SummaryEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    public function generate(Request $request, ChairSummaryAdapter $adapter): JsonResponse
    {
        $coordinatorId = $request->input('coordinatorId');
        $sectionId = $request->input('sectionId');
        $week = $request->integer('week');
        $useGPT = (bool) $request->input('useGPT');

        // Fetch weekly entries of all students under coordinator (and section if specified)
        $query = DB::table('weekly_entries as we')
            ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
            ->join('students as s', 's.id', '=', 'we.student_id');

        if ($coordinatorId) {
            $query->where('s.coordinator_id', $coordinatorId);
        }
        if ($sectionId) {
            $query->where('s.section_id', $sectionId);
            \Log::info('ChairSummary: Filtering by section_id: ' . $sectionId);
        }
        if ($week) {
            $query->where('we.week_number', $week);
        }

        $rows = $query->get();
        \Log::info('ChairSummary: Found ' . $rows->count() . ' weekly entries for coordinator ' . $coordinatorId . ', section ' . $sectionId . ', week ' . $week);
        
        // Log sample student IDs for debugging
        if ($rows->count() > 0) {
            $studentIds = DB::table('students as s')
                ->select('s.id', 's.section_id', 's.coordinator_id')
                ->where('s.coordinator_id', $coordinatorId)
                ->when($sectionId, function($q) use ($sectionId) {
                    return $q->where('s.section_id', $sectionId);
                })
                ->get();
            \Log::info('ChairSummary: Students matching criteria: ' . json_encode($studentIds));
        }
        
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
        
        // Create a hash of the weekly entries data to detect changes
        // This ensures PO analysis is consistent as long as the data hasn't changed
        $dataHash = hash('sha256', json_encode([
            'activities' => $activities,
            'learnings' => $learnings,
            'coordinator_id' => $coordinatorId,
            'section_id' => $sectionId,
            'week' => $week
        ]));
        
        // Check if we have cached PO analysis for this exact data
        $cacheQuery = DB::table('po_analysis_cache')
            ->where('coordinator_id', $coordinatorId)
            ->where('data_hash', $dataHash);
        
        if ($sectionId) {
            $cacheQuery->where('section_id', $sectionId);
        } else {
            $cacheQuery->whereNull('section_id');
        }
        
        if ($week) {
            $cacheQuery->where('week_number', $week);
        } else {
            $cacheQuery->whereNull('week_number');
        }
        
        $cached = $cacheQuery->first();
        
        if ($cached) {
            // Use cached results - consistent across refreshes
            \Log::info('ChairSummary: Using cached PO analysis for hash: ' . substr($dataHash, 0, 8) . '...');
            
            // Use current activities/learnings for evaluation (not cached ones)
            // This ensures evaluation is always based on current data
            // Ensure activities and learnings are arrays
            $resultActivities = is_array($activities) ? $activities : [];
            $resultLearnings = is_array($learnings) ? $learnings : [];
            
            \Log::info('ChairSummary: Cached data retrieved', [
                'has_summary' => !empty($cached->summary),
                'activities_count' => count($resultActivities),
                'learnings_count' => count($resultLearnings),
                'coordinator_id' => $coordinatorId
            ]);
            
            $result = [
                'summary' => $cached->summary,
                'pos_hit' => json_decode($cached->pos_hit, true) ?? [],
                'pos_not_hit' => json_decode($cached->pos_not_hit, true) ?? [],
                'poContextHit' => json_decode($cached->po_context_hit, true) ?? [],
                'poWordHit' => json_decode($cached->po_word_hit, true) ?? [],
                'recommendations' => json_decode($cached->recommendations, true) ?? [],
                'activities' => $resultActivities, // Always use current activities for evaluation
                'learnings' => $resultLearnings,   // Always use current learnings for evaluation
                'cached' => true,
            ];
        } else {
            // No cache found - generate new analysis
            \Log::info('ChairSummary: No cache found, generating new PO analysis');
            
            // Combined text for summary generation (can vary)
            $combined = $rows->map(function ($r) {
                $t = trim(($r->tasks ?? '') . ' ' . ($r->learnings ?? ''));
                $t = preg_replace('/\s+/', ' ', $t);
                if ($t && !preg_match('/[.!?]$/', $t)) { $t .= '.'; }
                return $t;
            })->filter()->implode(' ');

            // Enforce third-person phrasing before summarization (handles fallback too)
            $combined = $this->convertToThirdPerson($combined);

            \Log::info('ChairSummary: Combined text length: ' . strlen($combined));
            \Log::info('ChairSummary: Activities count: ' . count($activities));
            \Log::info('ChairSummary: Learnings count: ' . count($learnings));
            
            // Log sample activities and learnings for debugging
            if (!empty($activities)) {
                \Log::info('ChairSummary: Sample activities: ' . json_encode(array_slice($activities, 0, 3)));
            }
            if (!empty($learnings)) {
                \Log::info('ChairSummary: Sample learnings: ' . json_encode(array_slice($learnings, 0, 3)));
            }

            // Use adapter - pass activities and learnings separately for PO analysis
            // Summary generation is separate from PO analysis
            $result = $adapter->summarize($combined, $week, $useGPT, $activities, $learnings);
            
            // Check if OpenAI was unavailable
            if (isset($result['openai_unavailable']) && $result['openai_unavailable']) {
                // Return error response immediately without caching
                return response()->json($result, 503, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                ]);
            }
            
            // Save to cache for future use (only if we have activities/learnings and OpenAI succeeded)
            if ((!empty($activities) || !empty($learnings)) && !isset($result['error'])) {
                try {
                    DB::table('po_analysis_cache')->insert([
                        'coordinator_id' => $coordinatorId,
                        'section_id' => $sectionId ?? null,
                        'week_number' => $week ?? null,
                        'data_hash' => $dataHash,
                        'pos_hit' => json_encode($result['pos_hit'] ?? []),
                        'pos_not_hit' => json_encode($result['pos_not_hit'] ?? []),
                        'po_context_hit' => json_encode($result['poContextHit'] ?? []),
                        'po_word_hit' => json_encode($result['poWordHit'] ?? []),
                        'recommendations' => json_encode($result['recommendations'] ?? []),
                        'summary' => $result['summary'] ?? null,
                        'activities' => json_encode($activities),
                        'learnings' => json_encode($learnings),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    \Log::info('ChairSummary: Saved PO analysis to cache with hash: ' . substr($dataHash, 0, 8) . '...');
                } catch (\Exception $e) {
                    \Log::error('ChairSummary: Failed to save cache: ' . $e->getMessage());
                }
            }
        }
        // Ensure result summary is also third-person (extra safety for any model variation)
        if (isset($result['summary'])) {
            $result['summary'] = $this->convertToThirdPerson($result['summary']);
            if (!empty($week)) {
                $result['summary'] = $this->enforceWeekPrefix($result['summary']);
            }
        }
        
        \Log::info('ChairSummary: Result from adapter:', $result);

        // EVALUATION: Always run evaluation, even with cached data
        // This ensures evaluation metrics are always up-to-date and displayed
        // Build reference text from current activities and learnings (not cached)
        $summary = $result['summary'] ?? '';
        
        // Use current activities/learnings from result (which are always current, not cached)
        // Ensure they are arrays (they should be, but handle edge cases)
        $currentActivities = $result['activities'] ?? $activities;
        $currentLearnings = $result['learnings'] ?? $learnings;
        
        // Convert to arrays if they're JSON strings (from cache)
        if (is_string($currentActivities)) {
            $decoded = json_decode($currentActivities, true);
            $currentActivities = is_array($decoded) ? $decoded : [$currentActivities];
        }
        if (is_string($currentLearnings)) {
            $decoded = json_decode($currentLearnings, true);
            $currentLearnings = is_array($decoded) ? $decoded : [$currentLearnings];
        }
        
        // Ensure they are arrays
        if (!is_array($currentActivities)) {
            $currentActivities = !empty($currentActivities) ? [$currentActivities] : [];
        }
        if (!is_array($currentLearnings)) {
            $currentLearnings = !empty($currentLearnings) ? [$currentLearnings] : [];
        }
        
        $referenceText = $this->buildReferenceText($currentActivities, $currentLearnings);
        
        // Debug: Log evaluation preparation
        \Log::info('ChairSummary: Evaluation preparation', [
            'has_summary' => !empty($summary),
            'summary_length' => strlen($summary),
            'summary_preview' => substr($summary, 0, 100),
            'activities_count' => is_array($currentActivities) ? count($currentActivities) : 0,
            'activities_type' => gettype($currentActivities),
            'learnings_count' => is_array($currentLearnings) ? count($currentLearnings) : 0,
            'learnings_type' => gettype($currentLearnings),
            'reference_length' => strlen($referenceText),
            'reference_preview' => substr($referenceText, 0, 100),
            'has_reference' => !empty($referenceText),
            'using_cached' => isset($result['cached']) && $result['cached'],
            'coordinator_id' => $coordinatorId,
            'section_id' => $sectionId,
            'week' => $week
        ]);
        
        // Always evaluate if we have both summary and reference text
        $evaluationResults = null;
        if (!empty($summary) && !empty($referenceText)) {
            try {
                \Log::info('ChairSummary: Starting summary evaluation', [
                    'summary_length' => strlen($summary),
                    'reference_length' => strlen($referenceText),
                    'coordinator_id' => $coordinatorId,
                    'section_id' => $sectionId,
                    'week' => $week
                ]);
                
                \Log::info('ChairSummary: Calling evaluation service', [
                    'summary_length' => strlen($summary),
                    'reference_length' => strlen($referenceText),
                    'coordinator_id' => $coordinatorId
                ]);
                
                $evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
                
                \Log::info('ChairSummary: Evaluation service returned', [
                    'has_results' => !empty($evaluationResults),
                    'has_rouge1' => isset($evaluationResults['rouge1']),
                    'has_rouge2' => isset($evaluationResults['rouge2']),
                    'has_rougeL' => isset($evaluationResults['rougeL']),
                    'has_bertScore' => isset($evaluationResults['bertScore']),
                    'coordinator_id' => $coordinatorId
                ]);
                
                // Log evaluation results to console and Laravel log
                $this->evaluationService->logResults($evaluationResults, 'Chairperson Summary (ChairSummaryController)');
                
                \Log::info('ChairSummary: Evaluation completed', [
                    'rouge1_f1' => $evaluationResults['rouge1']['f1'] ?? 'N/A',
                    'rouge2_f1' => $evaluationResults['rouge2']['f1'] ?? 'N/A',
                    'rougeL_f1' => $evaluationResults['rougeL']['f1'] ?? 'N/A',
                    'bertScore' => $evaluationResults['bertScore'] ?? 'N/A',
                    'coordinator_id' => $coordinatorId,
                    'section_id' => $sectionId,
                    'week' => $week
                ]);
            } catch (\Throwable $e) {
                \Log::error('ChairSummary: Evaluation error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'coordinator_id' => $coordinatorId,
                    'section_id' => $sectionId,
                    'week' => $week
                ]);
            }
        } else {
            \Log::warning('ChairSummary: Evaluation skipped', [
                'reason' => empty($summary) ? 'Empty summary' : 'Empty reference text',
                'summary_empty' => empty($summary),
                'reference_empty' => empty($referenceText),
                'coordinator_id' => $coordinatorId,
                'section_id' => $sectionId,
                'week' => $week
            ]);
        }
        
        // Always add evaluation results to response (even if null, so frontend knows evaluation was attempted)
        $result['evaluation'] = $evaluationResults;

        // Activities and learnings are already extracted and stored in result by adapter
        // For "overall" week, ensure summary format
        if ($week === null || $week === 0) {
            $result['summary for this section on a week'] = $result['summary'] ?? '';
        }

        return response()->json($result, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    /**
     * Generate PO analysis for a single student (for coordinators)
     */
    public function generateForStudent(Request $request, ChairSummaryAdapter $adapter): JsonResponse
    {
        $studentId = $request->input('studentId');
        $week = $request->integer('week');
        $useGPT = (bool) $request->input('useGPT', true);
        
        // Verify coordinator has access to this student
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Check if user is coordinator and student belongs to them
        if ($user->hasRole('coordinator')) {
            $student = DB::table('students')->where('id', $studentId)->first();
            if (!$student || $student->coordinator_id != $user->id) {
                return response()->json(['error' => 'Access denied'], 403);
            }
        } elseif (!$user->hasRole('chairperson')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Fetch weekly entries for this specific student
        $query = DB::table('weekly_entries as we')
            ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
            ->where('we.student_id', $studentId);

        if ($week) {
            $query->where('we.week_number', $week);
        }

        $rows = $query->get();
        
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
        
        // Combined text for summary generation
        $combined = $rows->map(function ($r) {
            $t = trim(($r->tasks ?? '') . ' ' . ($r->learnings ?? ''));
            $t = preg_replace('/\s+/', ' ', $t);
            if ($t && !preg_match('/[.!?]$/', $t)) { $t .= '.'; }
            return $t;
        })->filter()->implode(' ');

        // Enforce third-person phrasing
        $combined = $this->convertToThirdPerson($combined);

        // Use adapter - pass activities and learnings separately for PO analysis
        $result = $adapter->summarize($combined, $week, $useGPT, $activities, $learnings);
        
        // Check if OpenAI was unavailable
        if (isset($result['openai_unavailable']) && $result['openai_unavailable']) {
            return response()->json($result, 503, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        }
        
        // Ensure result summary is third-person
        if (isset($result['summary'])) {
            $result['summary'] = $this->convertToThirdPerson($result['summary']);
            if (!empty($week)) {
                $result['summary'] = $this->enforceWeekPrefix($result['summary']);
            }
        }

        return response()->json($result, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    private function enforceWeekPrefix(string $text): string
    {
        $t = trim($text);
        if ($t === '') {
            return '';
        }
        if (preg_match('/^For\s+this\s+week,\s+those\s+students/i', $t)) {
            return $t;
        }
        $t = preg_replace('/^(In\s+week\s+\d+\s*,\s*|This\s+week\s*,\s*|In\s+this\s+week\s*,\s*)/i', '', $t);
        return 'For this week, those students ' . ltrim($t);
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
            '/\bwe\b/i' => 'the students',
            '/\bus\b/i' => 'the students',
            '/\bour\b/i' => 'the students\'',
            '/\bours\b/i' => 'the students\'',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }
    
    /**
     * Build reference text from raw database data for evaluation
     * 
     * @param array $activities Raw activities from database
     * @param array $learnings Raw learnings from database
     * @return string Combined reference text
     */
    private function buildReferenceText($activities, $learnings): string
    {
        $parts = [];
        
        // Ensure activities is an array
        if (!is_array($activities)) {
            $activities = is_string($activities) ? [$activities] : [];
        }
        
        // Ensure learnings is an array
        if (!is_array($learnings)) {
            $learnings = is_string($learnings) ? [$learnings] : [];
        }
        
        // Add activities
        if (!empty($activities)) {
            $activitiesText = array_filter(array_map('trim', $activities));
            if (!empty($activitiesText)) {
                $parts[] = 'Activities: ' . implode(' ', $activitiesText);
            }
        }
        
        // Add learnings
        if (!empty($learnings)) {
            $learningsText = array_filter(array_map('trim', $learnings));
            if (!empty($learningsText)) {
                $parts[] = 'Learnings: ' . implode(' ', $learningsText);
            }
        }
        
        return implode(' ', $parts);
    }
}


