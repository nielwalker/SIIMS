<?php

namespace App\Services;

use App\Services\NLPSummarizationService;

class SummaryAdapter
{
    protected $nlpService;

    public function __construct(NLPSummarizationService $nlpService)
    {
        $this->nlpService = $nlpService;
    }

    /**
     * Analyze a combined text and return summary and keyword scores.
     * Uses NLP-based summarization instead of OpenAI for faster processing.
     *
     * @param string $text
     * @param string|null $analysisType 'chairman' | 'coordinator' | null
     * @param bool $useGPT (deprecated - kept for compatibility, but NLP is always used)
     * @param int|null $week Week number (optional, passed directly from controller)
     * @return array{ summary: string, keywordScores: array<int,int>, usedGPT: bool }
     */
    public function analyze(string $text, ?string $analysisType, bool $useGPT = false, ?int $week = null): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        // Use week number if provided, otherwise try to extract from text
        $weekNumber = $week;
        if ($weekNumber === null && preg_match('/^\[WEEK\s+(\d+)\]\s*/i', $clean, $m)) {
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

        // Use NLP-based summarization (faster, no API dependency)
        $summary = 'No journal entries found.';
        $usedGPT = false; // Always false now - using NLP instead

        if ($clean) {
            try {
                if ($analysisType === 'coordinator') {
                    $summary = $this->nlpService->summarizeForCoordinator($clean, $weekNumber);
                } elseif ($analysisType === 'chairman') {
                    $summary = $this->nlpService->summarizeForChairperson($clean, $weekNumber);
                } else {
                    // Default to coordinator style
                    $summary = $this->nlpService->summarizeForCoordinator($clean, $weekNumber);
                }
            } catch (\Throwable $e) {
                \Log::error('NLP Summarization Error in SummaryAdapter:', ['message' => $e->getMessage()]);
                $summary = $clean ?: 'No journal entries found.';
            }
        }

        return [
            'summary' => $summary,
            'keywordScores' => $scores,
            'usedGPT' => $usedGPT,
        ];
    }
}


