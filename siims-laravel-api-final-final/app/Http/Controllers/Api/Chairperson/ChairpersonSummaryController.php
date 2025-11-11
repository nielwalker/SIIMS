<?php

namespace App\Http\Controllers\Api\Chairperson;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Chairperson\ChairSummaryAdapter;
use App\Services\OpenAI\SummaryEvaluationService;
use App\Services\OpenAI\Traits\TextProcessingTrait;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\Facades\Log;

class ChairpersonSummaryController extends Controller
{
    use TextProcessingTrait;

    protected $evaluationService;
    protected $openAIService;

    public function __construct(SummaryEvaluationService $evaluationService, OpenAIService $openAIService)
    {
        $this->evaluationService = $evaluationService;
        $this->openAIService = $openAIService;
    }

    /**
     * Handle OPTIONS request for CORS preflight
     */
    public function options(Request $request): JsonResponse
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = ['http://localhost:3000', 'http://127.0.0.1:3000', env('FRONTEND_URL', 'http://localhost:3000')];
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
        
        $response = response()->json(null, 204);
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
        return $response;
    }

    public function generate(Request $request, ChairSummaryAdapter $adapter): JsonResponse
    {
        // Increase execution time limit for large datasets (30+ students)
        set_time_limit(120); // 2 minutes for processing large datasets
        
        $coordinatorId = $request->input('coordinatorId');
        $sectionId = $request->input('sectionId');
        $week = $request->integer('week');
        $useGPT = (bool) $request->input('useGPT');

        // OPTIMIZED: Fetch weekly entries of all students under coordinator (and section if specified)
        // This query is optimized to handle many students efficiently
        $query = DB::table('weekly_entries as we')
            ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
            ->join('students as s', 's.id', '=', 'we.student_id')
            ->whereNotNull('we.tasks') // Only get entries with actual content
            ->whereNotNull('we.learnings');

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

        // OPTIMIZATION: Limit the number of entries processed to prevent timeout
        // For large datasets, we'll process in chunks or limit results
        // This ensures the system can handle coordinators with many students
        $maxEntries = 1000; // Maximum entries to process at once
        $rows = $query
            ->orderBy('we.created_at', 'desc') // Get most recent entries first
            ->limit($maxEntries)
            ->get();
        
        \Log::info('ChairSummary: Processing ' . $rows->count() . ' entries (max: ' . $maxEntries . ') for coordinator ' . $coordinatorId . ', section ' . $sectionId . ', week ' . $week);
        
        // Log sample student IDs for debugging (limit to prevent large JSON encoding)
        if ($rows->count() > 0) {
            $studentCount = DB::table('students as s')
                ->where('s.coordinator_id', $coordinatorId)
                ->when($sectionId, function($q) use ($sectionId) {
                    return $q->where('s.section_id', $sectionId);
                })
                ->count();
            
            // Only log sample IDs if reasonable number, otherwise just count
            if ($studentCount <= 50) {
                $studentIds = DB::table('students as s')
                    ->select('s.id', 's.section_id', 's.coordinator_id')
                    ->where('s.coordinator_id', $coordinatorId)
                    ->when($sectionId, function($q) use ($sectionId) {
                        return $q->where('s.section_id', $sectionId);
                    })
                    ->get();
                \Log::info('ChairSummary: Students matching criteria: ' . json_encode($studentIds));
            } else {
                \Log::info('ChairSummary: Students matching criteria: ' . $studentCount . ' students (too many to log)');
            }
        }
        
        // Extract activities/tasks and learnings separately for PO analysis
        // OPTIMIZED: Process in chunks if there are many entries to prevent memory issues
        $extracted = $this->extractActivitiesAndLearnings($rows);
        $activities = $extracted['activities'];
        $learnings = $extracted['learnings'];
        
        // Log summary for monitoring
        \Log::info('ChairSummary: Extracted data', [
            'activities_count' => count($activities),
            'learnings_count' => count($learnings),
            'coordinator_id' => $coordinatorId,
            'section_id' => $sectionId,
            'week' => $week
        ]);
        
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
            
            $cachedPosNotHit = json_decode($cached->pos_not_hit, true) ?? [];
            $cachedRecommendations = json_decode($cached->recommendations, true) ?? [];
            
            // Log cached recommendations for debugging
            \Log::info('ChairSummary: Cached recommendations check', [
                'has_recommendations' => !empty($cachedRecommendations),
                'recommendations_count' => count($cachedRecommendations),
                'recommendations_preview' => array_slice($cachedRecommendations, 0, 3),
                'pos_not_hit_count' => count($cachedPosNotHit)
            ]);
            
            // If cache has empty recommendations but has pos_not_hit, force regeneration
            // This handles cases where cache was created before recommendations were properly implemented
            if (empty($cachedRecommendations) && !empty($cachedPosNotHit)) {
                \Log::warning('ChairSummary: Cache has empty recommendations but has pos_not_hit - forcing regeneration', [
                    'pos_not_hit_count' => count($cachedPosNotHit),
                    'cache_id' => $cached->id ?? 'unknown'
                ]);
                
                // Delete the cache entry to force regeneration
                try {
                    DB::table('po_analysis_cache')
                        ->where('coordinator_id', $coordinatorId)
                        ->when($sectionId, function($q) use ($sectionId) {
                            return $q->where('section_id', $sectionId);
                        }, function($q) {
                            return $q->whereNull('section_id');
                        })
                        ->when($week, function($q) use ($week) {
                            return $q->where('week_number', $week);
                        }, function($q) {
                            return $q->whereNull('week_number');
                        })
                        ->where('data_hash', $dataHash)
                        ->delete();
                    \Log::info('ChairSummary: Deleted cache entry to force regeneration with recommendations');
                } catch (\Exception $e) {
                    \Log::error('ChairSummary: Failed to delete cache entry: ' . $e->getMessage());
                }
                
                // Fall through to generate new analysis (don't use cached data)
                $cached = null;
            }
            
            // Only use cached data if we didn't delete it above
            if ($cached) {
                // Use recommendations directly from cache (generated by OpenAI, no hard-coded fallbacks)
                $result = [
                    'summary' => $cached->summary,
                    'pos_hit' => json_decode($cached->pos_hit, true) ?? [],
                    'pos_not_hit' => $cachedPosNotHit,
                    'poContextHit' => json_decode($cached->po_context_hit, true) ?? [],
                    'poWordHit' => json_decode($cached->po_word_hit, true) ?? [],
                    'recommendations' => $cachedRecommendations, // Use OpenAI-generated recommendations from cache
                    'activities' => $resultActivities, // Always use current activities for evaluation
                    'learnings' => $resultLearnings,   // Always use current learnings for evaluation
                    'cached' => true,
                ];
            }
        }
        
        // Generate new analysis if cache was not found or was deleted due to missing recommendations
        if (!isset($result)) {
            // No cache found or cache was invalid - generate new analysis
            \Log::info('ChairSummary: Generating new PO analysis (cache not found or invalid)');
            
            // Combined text for summary generation (can vary)
            $combined = $this->buildCombinedText($rows);

            // Enforce third-person phrasing before summarization (handles fallback too)
            $combined = $this->convertToThirdPerson($combined, 'the student', 'the students');

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
            // Set a timeout for the entire operation to prevent hanging
            $startTime = microtime(true);
            
            // For large datasets (30+ students), limit the data sent to OpenAI to prevent timeouts
            // OpenAI has token limits and processing large amounts of data can cause timeouts
            $maxActivities = 50; // Limit activities to prevent timeout
            $maxLearnings = 50;  // Limit learnings to prevent timeout
            
            $limitedActivities = array_slice($activities, 0, $maxActivities);
            $limitedLearnings = array_slice($learnings, 0, $maxLearnings);
            
            if (count($activities) > $maxActivities || count($learnings) > $maxLearnings) {
                \Log::info('ChairSummary: Limiting data for OpenAI', [
                    'original_activities' => count($activities),
                    'limited_activities' => count($limitedActivities),
                    'original_learnings' => count($learnings),
                    'limited_learnings' => count($limitedLearnings),
                ]);
            }
            
            $result = $adapter->summarize($combined, $week, $useGPT, $limitedActivities, $limitedLearnings);
            
            $executionTime = microtime(true) - $startTime;
            \Log::info('ChairSummary: OpenAI execution time', [
                'time' => round($executionTime, 2) . 's',
                'coordinator_id' => $coordinatorId,
                'section_id' => $sectionId,
                'week' => $week
            ]);
            
            // Check if OpenAI was unavailable
            if (isset($result['openai_unavailable']) && $result['openai_unavailable']) {
                // Return error response immediately without caching
                $origin = $request->headers->get('Origin');
                $allowedOrigins = ['http://localhost:3000', 'http://127.0.0.1:3000', env('FRONTEND_URL', 'http://localhost:3000')];
                $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
                
                $errorResponse = response()->json($result, 503);
                $errorResponse->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
                $errorResponse->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PATCH');
                $errorResponse->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
                $errorResponse->headers->set('Access-Control-Allow-Credentials', 'false');
                $errorResponse->headers->set('Access-Control-Max-Age', '86400');
                return $errorResponse;
            }
            
            // Validate that OpenAI generated recommendations (log warning if missing, but don't add defaults)
            if (isset($result['pos_not_hit']) && isset($result['recommendations'])) {
                $notHitPOs = array_map(function($item) {
                    return is_string($item) ? $item : ($item['po'] ?? '');
                }, $result['pos_not_hit']);
                $notHitPOs = array_filter($notHitPOs, function($po) {
                    return !empty($po) && preg_match('/^PO\d+$/', $po);
                });
                
                // Check if OpenAI generated enough recommendations
                $recommendedPOs = [];
                foreach ($result['recommendations'] as $rec) {
                    if (is_string($rec) && preg_match_all('/PO\d+/', $rec, $matches)) {
                        foreach ($matches[0] as $po) {
                            $recommendedPOs[$po] = true;
                        }
                    }
                }
                $missingPOs = array_diff($notHitPOs, array_keys($recommendedPOs));
                if (!empty($missingPOs)) {
                    \Log::warning('Chairperson: OpenAI did not generate recommendations for all not met POs', [
                        'missing_pos' => $missingPOs,
                        'total_not_hit' => count($notHitPOs),
                        'recommendations_count' => count($result['recommendations'] ?? [])
                    ]);
                }
            }
            
            // Save to cache for future use (only if we have activities/learnings and OpenAI succeeded)
            // Use full activities/learnings for cache, not the limited ones sent to OpenAI
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
                        'activities' => json_encode($activities), // Use full activities for cache
                        'learnings' => json_encode($learnings), // Use full learnings for cache
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
        // NOTE: Don't add week prefix here - adapter already handles it
        if (isset($result['summary'])) {
            $result['summary'] = $this->convertToThirdPerson($result['summary'], 'the student', 'the students');
            // Remove duplicate prefixes if they exist
            $summary = $result['summary'];
            // Remove any duplicate week prefixes
            $summary = preg_replace('/^For\s+(this\s+week|week\s+\d+),\s+those\s+students\s+/i', '', $summary);
            $summary = preg_replace('/^For\s+overall,\s+the\s+students\s+/i', '', $summary);
            // Ensure it starts with the correct prefix based on week
            // If week is null or 0, it means "overall"
            if (!empty($week) && $week > 0) {
                $expectedPrefix = "For week {$week}, those students ";
            } else {
                // Overall case - use "For overall, the students"
                $expectedPrefix = 'For overall, the students ';
            }
            // Only add prefix if it doesn't already start with it
            if (!preg_match('/^' . preg_quote($expectedPrefix, '/') . '/i', $summary)) {
                $summary = $expectedPrefix . ltrim($summary);
            }
            $result['summary'] = $summary;
        }
        
        \Log::info('ChairSummary: Result from adapter:', $result);

        // EVALUATION: Always run evaluation, even with cached data
        // This ensures evaluation metrics are always up-to-date and displayed
        // Build reference text from current activities and learnings (not cached)
        $summary = $result['summary'] ?? '';
        
        // Use current activities/learnings from result (which are always current, not cached)
        // Ensure we use the FULL activities/learnings, not the limited ones sent to OpenAI
        // The result might have limited data, so always use the full $activities and $learnings
        $currentActivities = $activities; // Always use full activities
        $currentLearnings = $learnings;   // Always use full learnings
        
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
                $this->evaluationService->logResults($evaluationResults, 'Chairperson Summary (ChairpersonSummaryController)');
                
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

        // Ensure full activities and learnings are in the result (not the limited ones sent to OpenAI)
        // This ensures the frontend gets all the data even though OpenAI only processed a subset
        $result['activities'] = $activities; // Always use full activities
        $result['learnings'] = $learnings;   // Always use full learnings

        // CRITICAL: Ensure recommendations are always included in the response
        // If recommendations are missing or empty, set to empty array to ensure frontend receives the field
        if (!isset($result['recommendations']) || !is_array($result['recommendations'])) {
            $result['recommendations'] = [];
        }
        
        // Log recommendations for debugging
        \Log::info('ChairSummary: Final result recommendations', [
            'has_recommendations' => !empty($result['recommendations']),
            'recommendations_count' => count($result['recommendations'] ?? []),
            'recommendations_preview' => array_slice($result['recommendations'] ?? [], 0, 3)
        ]);

        // Activities and learnings are already extracted and stored in result by adapter
        // For "overall" week, ensure summary format
        if ($week === null || $week === 0) {
            $result['summary for this section on a week'] = $result['summary'] ?? '';
        }

        $origin = $request->headers->get('Origin');
        $allowedOrigins = ['http://localhost:3000', 'http://127.0.0.1:3000', env('FRONTEND_URL', 'http://localhost:3000')];
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
        
        $response = response()->json($result, 200);
        // Ensure CORS headers are set using headers object for better compatibility
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
        return $response;
    }


}

