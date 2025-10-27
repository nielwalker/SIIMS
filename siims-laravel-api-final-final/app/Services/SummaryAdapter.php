<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SummaryAdapter
{
    /**
     * Analyze a combined text and return summary and keyword scores.
     * If $useGPT is true and OPENAI_API_KEY exists, attempts GPT. Always computes keyword scores.
     *
     * @param string $text
     * @param string|null $analysisType 'chairman' | 'coordinator' | null
     * @param bool $useGPT
     * @return array{ summary: string, keywordScores: array<int,int>, usedGPT: bool }
     */
    public function analyze(string $text, ?string $analysisType, bool $useGPT = false): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        // Extract week number if prefixed like: [WEEK N]
        $weekNumber = null;
        if (preg_match('/^\[WEEK\s+(\d+)\]\s*/i', $clean, $m)) {
            $weekNumber = (int)($m[1] ?? 0) ?: null;
            $clean = trim(preg_replace('/^\[WEEK\s+\d+\]\s*/i', '', $clean));
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

        $lower = mb_strtolower($clean);
        $counts = array_map(function ($set) use ($lower) {
            $c = 0;
            foreach ($set as $kw) {
                if (str_contains($lower, $kw)) { $c++; continue; }
                $words = explode(' ', $kw);
                if (count($words) > 1) {
                    foreach ($words as $w) { if (str_contains($lower, $w)) { $c++; continue 2; } }
                }
                $stem = preg_replace('/(ing|ed|es|s)$/', '', $kw);
                if (strlen($stem) > 3 && str_contains($lower, $stem)) { $c++; continue; }
                foreach ([$kw.'s', $kw.'ing', $kw.'ed', preg_replace('/s$/', '', $kw)] as $v) {
                    if (str_contains($lower, $v)) { $c++; break; }
                }
            }
            return $c;
        }, $keywordSets);
        $total = array_sum($counts) ?: 1;
        $scores = array_map(fn($c) => (int)round(($c / $total) * 100), $counts);

        $summary = $clean ?: 'No journal entries found.';
        $usedGPT = false;

        $apiKey = env('OPENAI_API_KEY');
        if ($useGPT && $apiKey && $clean) {
            try {
                $model = 'gpt-4o-mini';
                if ($analysisType === 'coordinator') {
                    $weekLabel = $weekNumber ? (string)$weekNumber : 'the selected week';
                    $sys = "You are a professional summarization assistant for BSIT internship journals. Create a single brief summary (2–3 sentences), clear and easy to read. Do NOT use first-person (no I, me, we). Start the summary with: 'In week {$weekLabel} this student ...'.";
                    $usr = "Learnings (cleaned):\n".$clean;
                } elseif ($analysisType === 'chairman') {
                    $sys = "You are an expert evaluator analyzing BSIT internship journals for chairpersons. Provide ONLY which Program Outcomes (POs) were achieved/not achieved, no narrative.";
                    $usr = "Entry:\n".$clean;
                } else {
                    $sys = 'You are an educational evaluator for BSIT internships.';
                    $usr = "Summarize briefly and identify relevant program outcomes. Entry:\n".$clean;
                }

                $resp = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $sys],
                        ['role' => 'user', 'content' => $usr],
                    ],
                    'temperature' => 0.6,
                    'max_tokens' => 200,
                ]);
                if ($resp->ok()) {
                    $data = $resp->json();
                    $content = $data['choices'][0]['message']['content'] ?? null;
                    if ($content) {
                        // If the model returned JSON, try to extract summary; otherwise use raw content
                        $match = [];
                        if (preg_match('/```json([\s\S]*?)```/i', $content, $match)) {
                            $json = trim($match[1]);
                            $decoded = json_decode($json, true);
                            if (is_array($decoded) && !empty($decoded['summary'])) {
                                $summary = trim((string)$decoded['summary']);
                            } else {
                                $summary = trim($content);
                            }
                        } else {
                            $summary = trim($content);
                        }
                        $usedGPT = true;
                    }
                }
            } catch (\Throwable $e) {
                // silent fallback to keyword summary
            }
        }

        return [
            'summary' => $summary,
            'keywordScores' => $scores,
            'usedGPT' => $usedGPT,
        ];
    }
}


