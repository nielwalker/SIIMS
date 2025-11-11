<?php

namespace App\Services\Chairperson;

use App\Services\OpenAI\OpenAIService;
use App\Services\Chairperson\ChairSummaryPromptBuilder;
use App\Services\Chairperson\ChairpersonSummaryService;

class ChairSummaryAdapter
{
    protected $openAIService;
    protected $promptBuilder;

    public function __construct(
        OpenAIService $openAIService,
        ChairSummaryPromptBuilder $promptBuilder
    ) {
        $this->openAIService = $openAIService;
        $this->promptBuilder = $promptBuilder;
    }

    private function normalizeSummary(?string $raw): string
    {
        if (!$raw) return '';
        $content = (string)$raw;
        $content = preg_replace_callback('/```json[\s\S]*?```/i', function ($m) {
            return preg_replace('/```json|```/i', '', $m[0]);
        }, $content) ?? $content;
        // try JSON
        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['summary for this section on a week'])) {
            return trim($decoded['summary for this section on a week']);
        }
        // regex extract
        if (preg_match('/"summary for this section on a week"\s*:\s*"([\s\S]*?)"/i', $content, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/"summary"\s*:\s*"([\s\S]*?)"/i', $content, $m)) {
            return trim($m[1]);
        }
        $content = preg_replace('/^\{\s*|\s*\}$/', '', $content) ?? $content;
        $content = preg_replace('/^"|"$/', '', $content) ?? $content;
        return trim($content);
    }

    private function extractPosArrays(?string $raw): array
    {
        $hit = [];
        $notHit = [];
        if (!$raw || trim($raw) === '') {
            return ['hit' => $hit, 'notHit' => $notHit];
        }
        $content = (string)$raw;
        
        // Try to extract JSON from code blocks first
        $content = preg_replace_callback('/```json[\s\S]*?```/i', function ($m) {
            return preg_replace('/```json|```/i', '', $m[0]);
        }, $content) ?? $content;
        
        // Try multiple JSON extraction methods - be aggressive in finding JSON
        $decoded = null;
        
        // Method 1: Try to find JSON object with pos_hit (greedy match, handles nested objects)
        if (preg_match('/\{[\s\S]*?"pos_hit"[\s\S]*?\}/', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[0], true);
            if (!is_array($decoded) || !isset($decoded['pos_hit'])) {
                $decoded = null; // Try next method if this didn't work
            }
        }
        
        // Method 2: Try to find the largest JSON object (might contain pos_hit)
        if (!$decoded && preg_match('/\{[\s\S]{20,}\}/', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[0], true);
        }
        
        // Method 3: Try decoding the entire content (if it's pure JSON)
        if (!$decoded) {
            $decoded = json_decode($content, true);
        }
        
        // Method 4: Try to extract JSON from code blocks (already handled above, but try again)
        if (!$decoded && preg_match('/```[\s\S]*?```/', $content)) {
            $cleaned = preg_replace('/```(?:json)?/i', '', $content);
            $cleaned = preg_replace('/```/', '', $cleaned);
            $decoded = json_decode(trim($cleaned), true);
        }
        
        // Method 5: Try to find JSON object by looking for opening and closing braces more carefully
        if (!$decoded) {
            $start = strpos($content, '{');
            if ($start !== false) {
                $braceCount = 0;
                $end = $start;
                for ($i = $start; $i < strlen($content); $i++) {
                    if ($content[$i] === '{') $braceCount++;
                    if ($content[$i] === '}') {
                        $braceCount--;
                        if ($braceCount === 0) {
                            $end = $i;
                            break;
                        }
                    }
                }
                if ($end > $start) {
                    $jsonStr = substr($content, $start, $end - $start + 1);
                    $decoded = json_decode($jsonStr, true);
                }
            }
        }
        
        // Log for debugging
        \Log::info('Extracting PO arrays', [
            'has_decoded' => is_array($decoded),
            'has_pos_hit' => isset($decoded['pos_hit']),
            'has_po_context_hit' => isset($decoded['po_context_hit']),
            'has_po_word_hit' => isset($decoded['po_word_hit']),
            'content_preview' => substr($content, 0, 200)
        ]);
        
        if (is_array($decoded)) {
            $hit = is_array($decoded['pos_hit'] ?? null) ? $decoded['pos_hit'] : [];
            $notHit = is_array($decoded['pos_not_hit'] ?? null) ? $decoded['pos_not_hit'] : [];
            
            // Normalize pos_hit items to ensure they have 'po' and 'reason' keys
            $hit = array_map(function($item) {
                if (is_string($item)) {
                    return ['po' => $item, 'reason' => 'Evidence found in activities and learnings'];
                }
                if (is_array($item)) {
                    return [
                        'po' => $item['po'] ?? $item[0] ?? '',
                        'reason' => $item['reason'] ?? $item[1] ?? 'Evidence found in activities and learnings'
                    ];
                }
                return null;
            }, $hit);
            $hit = array_filter($hit, function($item) {
                return is_array($item) && !empty($item['po']);
            });
            $hit = array_values($hit);
            
            // If pos_hit is empty but we have po_context_hit, use that as fallback
            if (empty($hit) && is_array($decoded['po_context_hit'] ?? null)) {
                $contextHits = $decoded['po_context_hit'];
                foreach ($contextHits as $poCode) {
                    if (is_string($poCode) && preg_match('/^PO\d+$/', $poCode)) {
                        $hit[] = [
                            'po' => $poCode,
                            'reason' => 'Achieved through contextual activities and practical application'
                        ];
                    }
                }
            }
            
            // Also check po_word_hit if pos_hit is still empty
            if (empty($hit) && is_array($decoded['po_word_hit'] ?? null)) {
                $wordHits = $decoded['po_word_hit'];
                foreach ($wordHits as $poCode) {
                    if (is_string($poCode) && preg_match('/^PO\d+$/', $poCode)) {
                        // Check if already added from context_hit
                        $exists = false;
                        foreach ($hit as $existing) {
                            if (($existing['po'] ?? '') === $poCode) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $hit[] = [
                                'po' => $poCode,
                                'reason' => 'Achieved through keyword matching and explicit evidence'
                            ];
                        }
                    }
                }
            }
            
            \Log::info('Extracted PO arrays result', ['hit_count' => count($hit), 'not_hit_count' => count($notHit)]);
        }
        
        return ['hit' => $hit, 'notHit' => $notHit];
    }

    private function extractRecommendations(?string $raw): array
    {
        $recommendations = [];
        if (!$raw) return $recommendations;
        $content = (string)$raw;
        $content = preg_replace_callback('/```json[\s\S]*?```/i', function ($m) {
            return preg_replace('/```json|```/i', '', $m[0]);
        }, $content) ?? $content;
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $recs = $decoded['recommendations'] ?? [];
            if (is_array($recs)) {
                $recommendations = array_values(array_filter(array_map(function($r) {
                    return is_string($r) ? trim($r) : (is_array($r) && isset($r['recommendation']) ? trim($r['recommendation']) : '');
                }, $recs)));
            }
        }
        return $recommendations;
    }

    private function extractPoHitTypes(?string $raw): array
    {
        $word = [];
        $context = [];
        if (!$raw) return ['word' => $word, 'context' => $context];
        $content = (string)$raw;
        $content = preg_replace_callback('/```json[\s\S]*?```/i', function ($m) {
            return preg_replace('/```json|```/i', '', $m[0]);
        }, $content) ?? $content;
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $w = $decoded['po_word_hit'] ?? [];
            $c = $decoded['po_context_hit'] ?? [];
            if (is_array($w)) $word = array_values(array_filter(array_map('strval', $w)));
            if (is_array($c)) $context = array_values(array_filter(array_map('strval', $c)));
        }
        return ['word' => $word, 'context' => $context];
    }

    private function formatPosExplanation(string $title, array $items): string
    {
        if (empty($items)) return $title.': None.';
        $lines = array_map(function($item) {
            if (is_string($item)) {
                return $item;
            }
            if (is_array($item)) {
                $po = $item['po'] ?? $item[0] ?? '';
                $reason = $item['reason'] ?? $item[1] ?? '';
                return $po . ($reason ? ': ' . $reason : '');
            }
            return '';
        }, $items);
        return $title.': '.implode('; ', $lines);
    }

    public function summarize(string $text, ?int $week, bool $useGPT = false, array $activities = [], array $learnings = []): array
    {
        // Use OpenAIService for consistent text cleaning
        $clean = $this->openAIService->cleanText($text);
        $summary = '';
        $usedGPT = false;

        // Generate summary using OpenAI
        if (!empty($clean) && $this->openAIService->isAvailable()) {
            try {
                $prompt = $this->promptBuilder->buildSummaryPrompt($activities, $learnings, '', 'overall_summary');
                $response = $this->openAIService->call($prompt, [
                    'model' => 'gpt-4o-mini',
                    'max_tokens' => 3000,
                    'temperature' => 0.2,
                    'timeout' => 90,
                ]);
                
                if ($response['success'] && $response['content']) {
                    $summary = $this->openAIService->cleanText($response['content']);
                    if ($week) {
                        $summary = $this->openAIService->enforceWeekPrefix($summary, "For week {$week}, those students ");
                    } else {
                        $summary = $this->openAIService->enforceWeekPrefix($summary, 'For this week, those students ');
                    }
                    $usedGPT = true;
                } else {
                    \Log::warning('OpenAI summarization failed in ChairSummaryAdapter', ['error' => $response['error'] ?? 'Unknown error']);
                    $summary = $clean ?: '';
                }
            } catch (\Throwable $e) {
                \Log::error('OpenAI Summarization Error in ChairSummaryAdapter:', ['message' => $e->getMessage()]);
                $summary = $clean ?: '';
            }
        } else {
            $summary = $clean ?: '';
        }

        // Use ChairpersonSummaryService for PO analysis
        if ($useGPT && !empty($clean)) {
            try {
                $chairpersonSummaryService = app(ChairpersonSummaryService::class);
                $result = $chairpersonSummaryService->generateSummaryWithPOAnalysis($clean, $week, $activities, $learnings);

                // Use OpenAI-generated summary (already set above)
                if (isset($result['summary'])) {
                    $result['summary'] = $summary; // Use OpenAI summary
                }

                // If OpenAI was used successfully for PO analysis, return the merged result
                if ($result['usedGPT'] || isset($result['error'])) {
                    // Store activities and learnings separately (used for PO analysis, stable data)
                    if (!empty($activities)) {
                        $result['corrected_activities'] = $activities;
                    }
                    if (!empty($learnings)) {
                        $result['corrected_learnings'] = $learnings;
                    }
                    // Ensure summary is set (OpenAI-generated)
                    $result['summary'] = $summary;
                    return $result;
                }
            } catch (\Throwable $e) {
                \Log::error('Error calling ChairpersonSummaryService', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Fall through to return error response
            }
            
            // If we reach here, OpenAI is not available for PO analysis
            // Return OpenAI summary with empty PO analysis
            return [
                'error' => 'OpenAI is not available right now',
                'openai_unavailable' => true,
                'summary' => $summary, // OpenAI-generated summary
                'usedGPT' => $usedGPT,
                'posHitExplanation' => '',
                'posNotHitExplanation' => '',
                'poWordHit' => [],
                'poContextHit' => [],
                'recommendations' => [],
                'pos_hit' => [],
                'pos_not_hit' => [],
            ];
        }
        
        // If not using GPT, return OpenAI summary with empty PO analysis
        return [
            'summary' => $summary, // OpenAI-generated summary
            'usedGPT' => $usedGPT,
            'posHitExplanation' => '',
            'posNotHitExplanation' => '',
            'poWordHit' => [],
            'poContextHit' => [],
            'recommendations' => [],
            'pos_hit' => [],
            'pos_not_hit' => [],
        ];
    }
}

