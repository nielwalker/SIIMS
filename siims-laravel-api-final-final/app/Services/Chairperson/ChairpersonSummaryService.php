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
            
            // Call OpenAI API with optimized settings for faster responses
            $response = $this->openAIService->call($prompt, [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 2000, // Reduced for faster responses
                'temperature' => 0.2,
                'timeout' => 45, // Reduced timeout for faster failure detection
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
                    $notHitPOs[] = $po; // Add to notHitPOs array for recommendation checking
                }
                
                // Ensure every PO in pos_not_hit has exactly one recommendation (expand ranges, fill missing)
                $recommendations = $this->ensureCompleteRecommendations($recommendations, $notHitPOs);
                
                // Validate that OpenAI generated recommendations for all not met POs
                $this->validateRecommendationsFromOpenAI($recommendations, $notHitPOs);
                
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

    /**
     * Ensure every PO in pos_not_hit has exactly one recommendation
     * Expands ranges (e.g., "PO6-PO9") and fills missing recommendations
     * 
     * @param array $recommendations Recommendations from OpenAI
     * @param array $notHitPOs Array of PO codes that are not met
     * @return array Complete recommendations array with one per PO
     */
    private function ensureCompleteRecommendations(array $recommendations, array $notHitPOs): array
    {
        if (empty($notHitPOs)) {
            return $recommendations;
        }

        $expandedRecommendations = [];
        $coveredPOs = [];

        // First, expand any recommendations that mention ranges or multiple POs
        foreach ($recommendations as $rec) {
            if (!is_string($rec)) {
                continue;
            }

            // Check if recommendation mentions a range like "PO6-PO9" or "PO6, PO7, PO8"
            if (preg_match_all('/PO(\d+)(?:-PO(\d+))?/', $rec, $rangeMatches, PREG_SET_ORDER)) {
                foreach ($rangeMatches as $match) {
                    $startPO = 'PO' . $match[1];
                    if (isset($match[2]) && !empty($match[2])) {
                        // Range found (e.g., PO6-PO9)
                        $startNum = (int)$match[1];
                        $endNum = (int)$match[2];
                        for ($i = $startNum; $i <= $endNum; $i++) {
                            $po = 'PO' . $i;
                            if (in_array($po, $notHitPOs) && !in_array($po, $coveredPOs)) {
                                // Create individual recommendation for this PO
                                $individualRec = preg_replace('/PO\d+-PO\d+/', $po, $rec);
                                $individualRec = preg_replace('/PO\d+(?:,\s*PO\d+)+/', $po, $individualRec);
                                $expandedRecommendations[] = $individualRec;
                                $coveredPOs[] = $po;
                            }
                        }
                    } else {
                        // Single PO found
                        if (in_array($startPO, $notHitPOs) && !in_array($startPO, $coveredPOs)) {
                            $expandedRecommendations[] = $rec;
                            $coveredPOs[] = $startPO;
                        }
                    }
                }
            } else {
                // Check for individual POs mentioned in the recommendation
                if (preg_match_all('/PO\d+/', $rec, $poMatches)) {
                    $foundPOs = array_unique($poMatches[0]);
                    $relevantPOs = array_intersect($foundPOs, $notHitPOs);
                    if (!empty($relevantPOs)) {
                        // If recommendation mentions multiple POs, create one per PO
                        foreach ($relevantPOs as $po) {
                            if (!in_array($po, $coveredPOs)) {
                                // Create individual recommendation focusing on this PO
                                $individualRec = $rec;
                                // If it mentions multiple POs, replace with just this one
                                if (count($relevantPOs) > 1) {
                                    $individualRec = preg_replace('/PO\d+/', $po, $individualRec, 1);
                                    $individualRec = preg_replace('/\s*(?:and|,)\s*PO\d+/', '', $individualRec);
                                }
                                $expandedRecommendations[] = $individualRec;
                                $coveredPOs[] = $po;
                            }
                        }
                    } else {
                        // Recommendation doesn't mention any PO, add it as-is
                        $expandedRecommendations[] = $rec;
                    }
                } else {
                    // Recommendation doesn't mention any PO, add it as-is
                    $expandedRecommendations[] = $rec;
                }
            }
        }

        // Find POs that don't have recommendations yet
        $missingPOs = array_diff($notHitPOs, $coveredPOs);

        // For missing POs, generate recommendations based on OpenAI's style
        // Use existing recommendations as templates to maintain consistency
        foreach ($missingPOs as $po) {
            $found = false;
            
            // Strategy 1: Try to find a generic recommendation without PO mention
            foreach ($expandedRecommendations as $rec) {
                if (is_string($rec) && !preg_match('/PO\d+/', $rec)) {
                    // Generic recommendation - adapt it for this PO
                    $poSpecificRec = rtrim($rec, '.') . ' to achieve ' . $po . '.';
                    $expandedRecommendations[] = $poSpecificRec;
                    $coveredPOs[] = $po;
                    $found = true;
                    break;
                }
            }
            
            // Strategy 2: If no generic recommendation, use the first recommendation as a template
            if (!$found && !empty($expandedRecommendations)) {
                $template = $expandedRecommendations[0];
                if (is_string($template)) {
                    // Extract the action/advice part (remove existing PO mentions)
                    $cleanedTemplate = preg_replace('/\s*(?:to achieve|for|regarding|related to)\s*PO\d+/i', '', $template);
                    $cleanedTemplate = preg_replace('/PO\d+/', '', $cleanedTemplate);
                    $cleanedTemplate = trim($cleanedTemplate);
                    
                    // Create new recommendation for this PO
                    $poSpecificRec = rtrim($cleanedTemplate, '.') . ' to achieve ' . $po . '.';
                    $expandedRecommendations[] = $poSpecificRec;
                    $coveredPOs[] = $po;
                    $found = true;
                }
            }
            
            // If still not found, log warning (no hardcoded fallback)
            if (!$found) {
                Log::warning('No recommendation found for PO in pos_not_hit - OpenAI did not generate one', [
                    'po' => $po,
                    'total_not_hit' => count($notHitPOs),
                    'recommendations_count' => count($expandedRecommendations),
                    'openai_recommendations' => $recommendations
                ]);
            }
        }

        // Return expanded recommendations (even if not all POs are covered)
        // The frontend will display whatever recommendations OpenAI generated
        // We don't add hardcoded fallbacks, but we ensure ranges are expanded and combined ones are split
        return $expandedRecommendations;
    }

    /**
     * Validate that OpenAI generated recommendations for all not met POs
     * Logs a warning if recommendations are missing (but doesn't add hard-coded defaults)
     * 
     * @param array $recommendations Recommendations from OpenAI
     * @param array $notHitPOs Array of PO codes that are not met
     * @return void
     */
    private function validateRecommendationsFromOpenAI(array $recommendations, array $notHitPOs): void
    {
        if (empty($notHitPOs)) {
            return;
        }

        // Extract POs mentioned in recommendations
        $recommendedPOs = [];
        foreach ($recommendations as $rec) {
            if (is_string($rec) && preg_match_all('/PO\d+/', $rec, $matches)) {
                foreach ($matches[0] as $po) {
                    $recommendedPOs[$po] = true;
                }
            }
        }

        // Find POs that don't have recommendations
        $missingPOs = [];
        foreach ($notHitPOs as $po) {
            if (!isset($recommendedPOs[$po])) {
                $missingPOs[] = $po;
            }
        }

        // Log warning if OpenAI didn't generate recommendations for all POs
        if (!empty($missingPOs)) {
            Log::warning('OpenAI did not generate recommendations for all not met POs', [
                'missing_pos' => $missingPOs,
                'total_not_hit' => count($notHitPOs),
                'recommendations_count' => count($recommendations),
                'expected_count' => count($notHitPOs)
            ]);
        } else {
            Log::info('OpenAI generated recommendations for all not met POs', [
                'total_not_hit' => count($notHitPOs),
                'recommendations_count' => count($recommendations)
            ]);
        }
    }
}

