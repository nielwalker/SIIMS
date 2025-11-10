<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\SummaryAdapter;

class SummaryController extends Controller
{
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

        // Build combined text: for coordinator summaries, use learnings-only with simple de-duplication
        if ($analysisType === 'coordinator') {
            // Simplified de-duplication: just use unique learnings, NLP will handle summarization
            $learnings = collect($reports)
                ->map(fn($r) => trim((string)($r['learnings'] ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->all();
            
            $combined = implode(' ', $learnings);
        } else {
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
        }

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

        // Pass week number to adapter for NLP summarization
        // NLP service will format the summary with proper week prefix
        $result = $adapter->analyze($combined, $analysisType, $useGPT, $week);
        $summary = $result['summary'];

        return response()->json([
            'summary' => $summary,
            'keywordScores' => $keywordScores, // Word-based text mining scores only
            'usedGPT' => (bool) $result['usedGPT'],
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }
}


