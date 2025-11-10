<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\SummaryAdapter;
use App\Services\OpenAI\SummaryEvaluationService;
use Illuminate\Support\Facades\Log;

class SummaryController extends Controller
{
    protected $evaluationService;

    public function __construct(SummaryEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    public function options(): JsonResponse
    {
        return response()->json(null, 204, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    public function generate(Request $request, SummaryAdapter $adapter): JsonResponse
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
            ->select('we.week_number as weekNumber', 'we.tasks as activities', 'we.learnings')
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

        $reports = $query->when(!$isOverall && $week, function ($q) use ($week) {
                $q->where('we.week_number', $week);
            })
            ->get()
            ->map(function ($r) {
                return [
                    'weekNumber' => $r->weekNumber,
                    'activities' => $r->activities,
                    'learnings' => $r->learnings,
                ];
            })
            ->toArray();

        // Build combined text: include both activities and learnings for all analysis types
        $combined = collect($reports)
            ->map(function ($r) {
                $txt = trim(($r['activities'] ?? '') . ' ' . ($r['learnings'] ?? ''));
                $txt = preg_replace('/\s+/', ' ', $txt);
                if ($txt && !preg_match('/[.!?]$/', $txt)) {
                    $txt .= '.';
                }
                return $txt;
            })
            ->filter()
            ->implode(' ');

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
        $result = $adapter->analyze($combined, $analysisType, $useGPT, $week);
        $summary = $result['summary'];

        // EVALUATION: Compare summary against raw database data
        // Build reference text from raw activities and learnings
        $referenceText = $this->buildReferenceText($reports, $analysisType);
        
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

        return response()->json([
            'summary' => $summary,
            'keywordScores' => $keywordScores, // Word-based text mining scores only
            'usedGPT' => (bool) $result['usedGPT'],
            'evaluation' => $evaluationResults, // Include evaluation in response for debugging
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }
    
    /**
     * Build reference text from raw database data for evaluation
     * 
     * @param array $reports Raw reports from database
     * @param string|null $analysisType 'coordinator' or 'chairman'
     * @return string Combined reference text
     */
    private function buildReferenceText(array $reports, ?string $analysisType): string
    {
        $parts = [];
        
        foreach ($reports as $report) {
            // Include both activities and learnings for all analysis types
            if (!empty($report['activities'])) {
                $parts[] = 'Activities: ' . trim($report['activities']);
            }
            if (!empty($report['learnings'])) {
                $parts[] = 'Learnings: ' . trim($report['learnings']);
            }
        }
        
        return implode(' ', $parts);
    }
}


