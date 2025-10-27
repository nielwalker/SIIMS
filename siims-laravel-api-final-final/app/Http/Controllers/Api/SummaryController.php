<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        // Build combined text: for coordinator summaries, use learnings-only with de-duplication
        if ($analysisType === 'coordinator') {
            $normalize = function (string $s): string {
                $s = strip_tags($s);
                $s = preg_replace('/[^\w\s\.,!?]/u', ' ', $s) ?? '';
                $s = preg_replace('/\s+/', ' ', $s) ?? '';
                return trim(mb_strtolower($s));
            };
            $similar = function (string $a, string $b): float {
                $wa = array_values(array_unique(array_filter(preg_split('/\s+/', $a) ?: [], fn($w) => mb_strlen($w) > 2)));
                $wb = array_values(array_unique(array_filter(preg_split('/\s+/', $b) ?: [], fn($w) => mb_strlen($w) > 2)));
                if (empty($wa) && empty($wb)) return 0.0;
                $inter = array_intersect($wa, $wb);
                $union = array_unique(array_merge($wa, $wb));
                return count($union) === 0 ? 0.0 : count($inter) / count($union);
            };
            $sentences = collect($reports)
                ->map(fn($r) => (string)($r['learnings'] ?? ''))
                ->filter()
                ->map(fn($t) => preg_split('/[.!?]+/', $t) ?: [])
                ->flatten()
                ->map(fn($s) => trim($s))
                ->filter(fn($s) => mb_strlen($s) > 5)
                ->map(fn($s) => $normalize($s))
                ->values()
                ->all();

            $compressed = [];
            foreach ($sentences as $s) {
                $dup = false;
                foreach ($compressed as $ex) {
                    if ($similar($s, $ex) > 0.7) { $dup = true; break; }
                }
                if (!$dup) { $compressed[] = $s; }
            }
            $combined = collect($compressed)
                ->map(function ($s) {
                    $s = trim($s);
                    if ($s === '') return '';
                    $s = mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
                    return preg_match('/[.!?]$/', $s) ? $s : ($s . '.');
                })
                ->filter()
                ->implode(' ');
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

        // Optionally call OpenAI when enabled
        // Pass week context by replacing placeholder {WEEK} in adapter prompts
        if ($week) {
            // Temporarily inject the week into combined text header to allow adapter to format opening
            $combined = "[WEEK {$week}] " . $combined;
        }
        $result = $adapter->analyze($combined, $analysisType, $useGPT);
        if ($week && isset($result['summary'])) {
            // Fix opening if model echoed [WEEK N]; ensure desired "In week N this student ..."
            $result['summary'] = preg_replace('/^\[WEEK\s+\d+\]\s*/i', '', $result['summary']);
        }
        $summary = $result['summary'];

        return response()->json([
            'summary' => $summary,
            'keywordScores' => $keywordScores,
            'usedGPT' => (bool) $result['usedGPT'],
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }
}


