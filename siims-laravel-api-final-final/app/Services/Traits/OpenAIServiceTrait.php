<?php

namespace App\Services\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait OpenAIServiceTrait
{
    /**
     * Call OpenAI API with the given prompt
     *
     * @param string $prompt
     * @param string $model
     * @param int $maxTokens
     * @param float $temperature
     * @param int $timeout
     * @return array
     */
    protected function callOpenAI(
        string $prompt,
        string $model = 'gpt-3.5-turbo',
        int $maxTokens = 500,
        float $temperature = 0.7,
        int $timeout = 30
    ): array {
        try {
            $apiKey = config('services.openai.api_key');

            if (!$apiKey) {
                Log::warning('OpenAI API key not configured');
                return ['success' => false, 'error' => 'API key not configured'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($timeout)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $summary = $data['choices'][0]['message']['content'] ?? '';

                return [
                    'success' => true,
                    'summary' => trim($summary)
                ];
            } else {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'API call failed'
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API Exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean text by removing quotes and normalizing whitespace
     *
     * @param mixed $text
     * @return string
     */
    protected function cleanText($text): string
    {
        if (!is_string($text)) {
            return '';
        }

        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                str_replace(['"', "'", '\\"', "\\'"], '', $text)
            )
        );
    }

    /**
     * Clean array of data by applying cleanText to each item
     *
     * @param mixed $data
     * @return array
     */
    protected function cleanDataArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        return array_map(function ($item) {
            return $this->cleanText($item);
        }, $data);
    }

    /**
     * Enforce week prefix for weekly summaries
     *
     * @param string $text
     * @param string $prefix
     * @return string
     */
    protected function enforceWeekPrefix(string $text, string $prefix = 'For this week, those students '): string
    {
        $t = trim($text);
        if ($t === '') {
            return $prefix . 'completed their weekly activities and learning outcomes.';
        }

        // Check if already starts with the desired phrase
        $escapedPrefix = preg_quote($prefix, '/');
        if (preg_match('/^' . $escapedPrefix . '/i', $t)) {
            return $t;
        }

        // Remove any leading connectors
        $t = preg_replace('/^(In\s+week\s+\d+\s*,\s*|This\s+week\s*,\s*|In\s+this\s+week\s*,\s*)/i', '', $t);
        return $prefix . ltrim($t);
    }
}

