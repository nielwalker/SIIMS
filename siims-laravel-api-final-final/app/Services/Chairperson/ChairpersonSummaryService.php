<?php

namespace App\Services\Chairperson;

use App\Services\OpenAI\OpenAIService;
use App\Services\Chairperson\ChairpersonPOPromptBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Chairperson Summary OpenAI Service
 * 
 * Handles OpenAI API calls for PO analysis ONLY (not summary generation) for chairperson views.
 * Summary generation is handled by OpenAI summarization services.
 */
class ChairpersonSummaryService
{
    protected $openAIService;
    protected $promptBuilder;

    public function __construct(OpenAIService $openAIService, ChairpersonPOPromptBuilder $promptBuilder)
    {
        $this->openAIService = $openAIService;
        $this->promptBuilder = $promptBuilder;
    }

    /**
     * Generate PO analysis using OpenAI for chairperson (multiple students)
     * NO summary generation - summary comes from OpenAI summarization
     *
     * @param string $text
     * @param int|null $week
     * @param array $activities
     * @param array $learnings
     * @return array
     */
    public function generateSummaryWithPOAnalysis(
        string $text,
        ?int $week = null,
        array $activities = [],
        array $learnings = []
    ): array {
        // Check if OpenAI is available
        if (!$this->openAIService->isAvailable()) {
            return $this->getUnavailableResponse();
        }

        // Use OpenAIService for consistent text cleaning
        $clean = $this->openAIService->cleanText($text);
        if (empty($clean)) {
            return $this->getUnavailableResponse();
        }

        try {
            // Build prompt using ChairpersonPOPromptBuilder
            $prompt = $this->promptBuilder->buildPOAnalysisPrompt($clean, $week, $activities, $learnings);
            
            // Call OpenAI API
            $response = $this->openAIService->call($prompt, [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 3000,
                'temperature' => 0.2,
                'timeout' => 90,
                'top_p' => 0.95,
            ]);

            if ($response['success'] && $response['content']) {
                $rawContent = $response['content'];
                
                // Extract PO analysis from response
                $pos = $this->extractPosArrays($rawContent);
                $poTypes = $this->extractPoHitTypes($rawContent);
                $recommendations = $this->extractRecommendations($rawContent);
                
                // Ensure all 15 POs are accounted for
                $allPOs = array_map(function($i) {
                    return 'PO' . ($i + 1);
                }, range(0, 14));
                
                $hitPOs = array_map(function($item) {
                    return is_string($item) ? $item : ($item['po'] ?? '');
                }, $pos['hit']);
                $hitPOs = array_filter($hitPOs, function($po) {
                    return !empty($po) && preg_match('/^PO\d+$/', $po);
                });
                
                $notHitPOs = array_map(function($item) {
                    return is_string($item) ? $item : ($item['po'] ?? '');
                }, $pos['notHit']);
                $notHitPOs = array_filter($notHitPOs, function($po) {
                    return !empty($po) && preg_match('/^PO\d+$/', $po);
                });
                
                // Find missing POs and add to pos_not_hit
                $missingPOs = array_diff($allPOs, array_merge($hitPOs, $notHitPOs));
                foreach ($missingPOs as $po) {
                    $pos['notHit'][] = [
                        'po' => $po,
                        'reason' => $this->getDefaultNotHitReason($po)
                    ];
                }
                
                return [
                    'summary' => '', // Summary is generated separately, not here
                    'usedGPT' => true,
                    'posHitExplanation' => $this->formatPosExplanation('Program Outcomes Achieved', $pos['hit']),
                    'posNotHitExplanation' => $this->formatPosExplanation('Program Outcomes Not Met', $pos['notHit']),
                    'poWordHit' => $poTypes['word'] ?? [],
                    'poContextHit' => $poTypes['context'] ?? [],
                    'recommendations' => $recommendations,
                    'pos_hit' => $pos['hit'],
                    'pos_not_hit' => $pos['notHit'],
                ];
            } else {
                Log::error('OpenAI API request failed in ChairpersonSummaryService', [
                    'error' => $response['error'] ?? 'Unknown error'
                ]);
                return $this->getUnavailableResponse();
            }
        } catch (\Throwable $e) {
            Log::error('OpenAI API Error in ChairpersonSummaryService', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getUnavailableResponse();
        }
    }

    /**
     * Get unavailable response
     */
    private function getUnavailableResponse(): array
    {
        return [
            'error' => 'OpenAI is not available right now',
            'openai_unavailable' => true,
            'summary' => '',
            'usedGPT' => false,
            'posHitExplanation' => '',
            'posNotHitExplanation' => '',
            'poWordHit' => [],
            'poContextHit' => [],
            'recommendations' => [],
            'pos_hit' => [],
            'pos_not_hit' => [],
        ];
    }

    /**
     * Extract PO arrays from OpenAI response
     */
    private function extractPosArrays(string $content): array
    {
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $content, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if (is_array($json)) {
                return [
                    'hit' => $json['pos_hit'] ?? [],
                    'notHit' => $json['pos_not_hit'] ?? [],
                ];
            }
        }
        return ['hit' => [], 'notHit' => []];
    }

    /**
     * Extract PO hit types from OpenAI response
     */
    private function extractPoHitTypes(string $content): array
    {
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $content, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if (is_array($json)) {
                return [
                    'word' => $json['po_word_hit'] ?? [],
                    'context' => $json['po_context_hit'] ?? [],
                ];
            }
        }
        return ['word' => [], 'context' => []];
    }

    /**
     * Extract recommendations from OpenAI response
     */
    private function extractRecommendations(string $content): array
    {
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $content, $jsonMatch)) {
            $json = json_decode($jsonMatch[0], true);
            if (is_array($json) && isset($json['recommendations'])) {
                return is_array($json['recommendations']) ? $json['recommendations'] : [];
            }
        }
        return [];
    }

    /**
     * Format PO explanation
     */
    private function formatPosExplanation(string $title, array $items): string
    {
        if (empty($items)) {
            return $title . ': None.';
        }
        $lines = array_map(function ($it) {
            $po = is_string($it['po'] ?? null) ? $it['po'] : (string)($it['po'] ?? '');
            $reason = is_string($it['reason'] ?? null) ? $it['reason'] : '';
            return trim($po . ' – ' . $reason);
        }, $items);
        return $title . ': ' . implode('; ', $lines);
    }

    /**
     * Get default reason for not hit PO
     */
    private function getDefaultNotHitReason(string $po): string
    {
        $reasons = [
            'PO1' => 'No evidence of mathematical or computational knowledge application.',
            'PO2' => 'No evidence of using current best practices and standards.',
            'PO3' => 'No evidence of analyzing complex computing problems.',
            'PO4' => 'No evidence of identifying and analyzing user needs.',
            'PO5' => 'No evidence of designing or implementing computing-based solutions.',
            'PO6' => 'No evidence of integrating IT solutions.',
            'PO7' => 'No evidence of using appropriate techniques and tools.',
            'PO8' => 'No evidence of working effectively in teams.',
            'PO9' => 'No evidence of effective project planning.',
            'PO10' => 'No evidence of effective communication.',
            'PO11' => 'No evidence of assessing local and global impact.',
            'PO12' => 'No evidence of professional and ethical responsibilities.',
            'PO13' => 'No evidence of continuing professional development.',
            'PO14' => 'No evidence of research and development participation.',
            'PO15' => 'No evidence of preserving Filipino historical and cultural heritage.',
        ];
        return $reasons[$po] ?? "No evidence of achieving {$po} based on activities and learnings.";
    }
}

