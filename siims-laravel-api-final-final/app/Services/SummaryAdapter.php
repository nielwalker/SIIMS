<?php

namespace App\Services;

use App\Services\OpenAI\OpenAIService;
use App\Services\OpenAI\CoordinatorPromptBuilder;
use App\Services\OpenAI\PromptBuilder;

class SummaryAdapter
{
    protected $openAIService;
    protected $coordinatorPromptBuilder;
    protected $chairPromptBuilder;

    public function __construct(
        OpenAIService $openAIService,
        CoordinatorPromptBuilder $coordinatorPromptBuilder,
        PromptBuilder $chairPromptBuilder
    ) {
        $this->openAIService = $openAIService;
        $this->coordinatorPromptBuilder = $coordinatorPromptBuilder;
        $this->chairPromptBuilder = $chairPromptBuilder;
    }

    /**
     * Analyze a combined text and return summary and keyword scores.
     * Uses OpenAI for summarization.
     *
     * @param string $text
     * @param string|null $analysisType 'chairman' | 'coordinator' | null
     * @param bool $useGPT Whether to use OpenAI (always true now)
     * @param int|null $week Week number (optional, passed directly from controller)
     * @return array{ summary: string, keywordScores: array<int,int>, usedGPT: bool }
     */
    public function analyze(string $text, ?string $analysisType, bool $useGPT = true, ?int $week = null): array
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

        // Use OpenAI for summarization
        $summary = 'No journal entries found.';
        $usedGPT = false;

        if ($clean && $useGPT && $this->openAIService->isAvailable()) {
            try {
                // Prepare activities and learnings for prompt building
                // For coordinator, focus on learnings; for chairperson, use both
                $activities = [];
                $learnings = [];
                
                if ($analysisType === 'coordinator') {
                    $learnings = [$clean];
                } else {
                    $learnings = [$clean];
                }
                
                // Build prompt based on analysis type
                if ($analysisType === 'coordinator') {
                    $prompt = $this->coordinatorPromptBuilder->buildPrompt($activities, $learnings, '');
                    $response = $this->openAIService->callSimple($prompt, 'gpt-3.5-turbo', 300, 0.6, 30);
                } else {
                    $prompt = $this->chairPromptBuilder->buildSummaryPrompt($activities, $learnings, '', 'overall_summary');
                    $response = $this->openAIService->call($prompt, [
                        'model' => 'gpt-4o-mini',
                        'max_tokens' => 3000,
                        'temperature' => 0.2,
                        'timeout' => 90,
                    ]);
                }
                
                if ($response['success']) {
                    $summary = $this->openAIService->cleanText($response['summary'] ?? $response['content'] ?? '');
                    if ($analysisType === 'coordinator' && !preg_match('/^For\s+this\s+week,\s+the\s+student/i', $summary)) {
                        $weekLabel = $weekNumber ? "week {$weekNumber}" : "this week";
                        $summary = "For {$weekLabel}, the student " . ltrim($summary);
                    } elseif ($analysisType === 'chairman' && !preg_match('/^For\s+this\s+week/i', $summary)) {
                        $weekLabel = $weekNumber ? "week {$weekNumber}" : "this week";
                        $summary = "For {$weekLabel}, those students " . ltrim($summary);
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


