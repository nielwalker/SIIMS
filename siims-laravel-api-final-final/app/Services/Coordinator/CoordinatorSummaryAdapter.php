<?php

namespace App\Services\Coordinator;

use App\Services\OpenAI\OpenAIService;
use App\Services\Coordinator\CoordinatorSummaryPromptBuilder;

/**
 * Coordinator Summary Adapter
 * 
 * Handles summary generation for coordinator view (single student).
 * Uses OpenAI for summarization.
 */
class CoordinatorSummaryAdapter
{
    protected $openAIService;
    protected $coordinatorPromptBuilder;

    public function __construct(
        OpenAIService $openAIService,
        CoordinatorSummaryPromptBuilder $coordinatorPromptBuilder
    ) {
        $this->openAIService = $openAIService;
        $this->coordinatorPromptBuilder = $coordinatorPromptBuilder;
    }

    /**
     * Analyze a combined text and return summary and keyword scores.
     * Uses OpenAI for summarization.
     *
     * @param string $text
     * @param string|null $analysisType 'coordinator' | null
     * @param bool $useGPT Whether to use OpenAI (always true now)
     * @param int|null $week Week number (optional, passed directly from controller)
     * @return array{ summary: string, keywordScores: array<int,int>, usedGPT: bool }
     */
    public function analyze(string $text, ?string $analysisType, bool $useGPT = true, ?int $week = null): array
    {
        // Use OpenAIService for consistent text cleaning
        $clean = $this->openAIService->cleanText($text);
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

        // Use OpenAI for summarization
        $summary = 'No journal entries found.';
        $usedGPT = false;

        if ($clean && $useGPT && $this->openAIService->isAvailable()) {
            try {
                // Prepare activities and learnings for prompt building
                $activities = [];
                $learnings = [$clean]; // Combined text includes both activities and learnings
                
                // Build prompt for coordinator
                $prompt = $this->coordinatorPromptBuilder->buildPrompt($activities, $learnings, '');
                // Optimized for faster responses: reduced tokens and timeout
                $response = $this->openAIService->callSimple($prompt, 'gpt-4o-mini', 250, 0.6, 25);
                
                if ($response['success']) {
                    $summary = $this->openAIService->cleanText($response['summary'] ?? $response['content'] ?? '');
                    if (!preg_match('/^For\s+this\s+week,\s+the\s+student/i', $summary)) {
                        $weekLabel = $weekNumber ? "week {$weekNumber}" : "this week";
                        $summary = "For {$weekLabel}, the student " . ltrim($summary);
                    }
                    $usedGPT = true;
                } else {
                    \Log::warning('OpenAI summarization failed in SummaryAdapter', ['error' => $response['error'] ?? 'Unknown error']);
                    $summary = $clean ?: 'No journal entries found.';
                }
            } catch (\Throwable $e) {
                \Log::error('OpenAI Summarization Error in SummaryAdapter:', ['message' => $e->getMessage()]);
                $summary = $clean ?: 'No journal entries found.';
            }
        } else {
            // Fallback if OpenAI is not available
            $summary = $clean ?: 'No journal entries found.';
        }

        return [
            'summary' => $summary,
            'keywordScores' => $scores,
            'usedGPT' => $usedGPT,
        ];
    }
}

