<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Unified OpenAI Service
 * 
 * Centralized service for all OpenAI API interactions.
 * Consolidates duplicate code from multiple adapters and controllers.
 */
class OpenAIService
{
    /**
     * Default configuration
     */
    private const DEFAULT_MODEL = 'gpt-4o-mini';
    private const DEFAULT_MAX_TOKENS = 4000; // Increased for better responses
    private const DEFAULT_TEMPERATURE = 0.2;
    private const DEFAULT_TIMEOUT = 60; // Increased timeout for better responses

    /**
     * Call OpenAI API with the given parameters
     *
     * @param string|array $messages System and user messages, or a single prompt string
     * @param array $options Optional configuration (model, max_tokens, temperature, timeout)
     * @return array{success: bool, content: string|null, error: string|null, raw: mixed}
     */
    public function call(
        $messages,
        array $options = []
    ): array {
        try {
            $apiKey = $this->getApiKey();
            
            if (!$apiKey) {
                Log::warning('OpenAI API key not configured');
                return [
                    'success' => false,
                    'content' => null,
                    'error' => 'API key not configured',
                    'raw' => null
                ];
            }

            // Normalize messages format
            $normalizedMessages = $this->normalizeMessages($messages);
            
            // Merge options with defaults
            $config = array_merge([
                'model' => self::DEFAULT_MODEL,
                'max_tokens' => self::DEFAULT_MAX_TOKENS,
                'temperature' => self::DEFAULT_TEMPERATURE,
                'timeout' => self::DEFAULT_TIMEOUT,
                'top_p' => 0.95,
            ], $options);

            // Configure HTTP client with SSL verification
            // For Windows development, we may need to disable SSL verification
            // In production, ensure proper CA certificates are configured
            $httpClient = Http::withToken($apiKey)
                ->timeout($config['timeout']);
            
            // Disable SSL verification for development (Windows SSL certificate issue)
            // Check if we're in a development environment or if explicitly disabled via env
            $disableSSLVerify = env('OPENAI_DISABLE_SSL_VERIFY', false);
            $isDevelopment = app()->environment(['local', 'development', 'testing']);
            
            if ($isDevelopment || $disableSSLVerify) {
                $httpClient = $httpClient->withOptions([
                    'verify' => false, // Disable SSL verification for development
                ]);
            }
            
            $response = $httpClient->post('https://api.openai.com/v1/chat/completions', [
                'model' => $config['model'],
                'messages' => $normalizedMessages,
                'temperature' => $config['temperature'],
                'max_tokens' => $config['max_tokens'],
                'top_p' => $config['top_p'] ?? 0.95,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                $usage = $data['usage'] ?? null; // Token usage information

                // Log token usage for monitoring
                if ($usage) {
                    Log::info('OpenAI Token Usage', [
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                    ]);
                }

                return [
                    'success' => true,
                    'content' => $content ? trim($content) : null,
                    'error' => null,
                    'raw' => $data,
                    'usage' => $usage // Include token usage in response
                ];
            } else {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'content' => null,
                    'error' => 'API call failed',
                    'raw' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'content' => null,
                'error' => $e->getMessage(),
                'raw' => null
            ];
        }
    }

    /**
     * Quick call with simple prompt (backward compatibility)
     *
     * @param string $prompt
     * @param string $model
     * @param int $maxTokens
     * @param float $temperature
     * @param int $timeout
     * @return array{success: bool, summary: string, error: string|null}
     */
    public function callSimple(
        string $prompt,
        string $model = 'gpt-3.5-turbo',
        int $maxTokens = 500,
        float $temperature = 0.7,
        int $timeout = 30
    ): array {
        $result = $this->call($prompt, [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'timeout' => $timeout,
        ]);

        return [
            'success' => $result['success'],
            'summary' => $result['content'] ?? '',
            'error' => $result['error'],
            'usage' => $result['usage'] ?? null // Include token usage
        ];
    }

    /**
     * Get OpenAI API key from environment
     *
     * @return string|null
     */
    private function getApiKey(): ?string
    {
        return env('OPENAI_API_KEY') ?: config('services.openai.api_key');
    }

    /**
     * Normalize messages to OpenAI format
     *
     * @param string|array $messages
     * @return array
     */
    private function normalizeMessages($messages): array
    {
        // If string, convert to user message
        if (is_string($messages)) {
            return [
                ['role' => 'user', 'content' => $messages]
            ];
        }

        // If array, ensure proper format
        if (is_array($messages)) {
            // Check if already in OpenAI format
            if (isset($messages[0]['role'])) {
                return $messages;
            }
            
            // If array of strings, convert to user messages
            return array_map(function ($msg) {
                return is_array($msg) ? $msg : ['role' => 'user', 'content' => $msg];
            }, $messages);
        }

        return [];
    }

    /**
     * Clean text by removing quotes and normalizing whitespace
     *
     * @param mixed $text
     * @return string
     */
    public function cleanText($text): string
    {
        if (!is_string($text)) {
            return '';
        }

        // Strip HTML tags and normalize whitespace
        $text = strip_tags($text);
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
    public function cleanDataArray($data): array
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
    public function enforceWeekPrefix(string $text, string $prefix = 'For this week, those students '): string
    {
        $t = trim($text);
        if ($t === '') {
            return $prefix . 'completed their weekly activities and learning outcomes.';
        }

        // Check if already starts with the desired phrase (exact match)
        $escapedPrefix = preg_quote($prefix, '/');
        if (preg_match('/^' . $escapedPrefix . '/i', $t)) {
            return $t;
        }

        // Remove any existing week prefixes to avoid duplication
        // Remove: "For this week, those students", "For week X, those students", "For overall, the students"
        $t = preg_replace('/^For\s+(this\s+week|week\s+\d+),\s+those\s+students\s+/i', '', $t);
        $t = preg_replace('/^For\s+overall,\s+the\s+students\s+/i', '', $t);
        $t = preg_replace('/^For\s+overall,\s+/i', '', $t); // Also handle "For overall, " without "the students"
        $t = preg_replace('/^For\s+this\s+week,\s+the\s+student\s+/i', '', $t);
        $t = preg_replace('/^For\s+week\s+\d+,\s+the\s+student\s+/i', '', $t);
        
        // Remove any other leading connectors
        $t = preg_replace('/^(In\s+week\s+\d+\s*,\s*|This\s+week\s*,\s*|In\s+this\s+week\s*,\s*)/i', '', $t);
        
        return $prefix . ltrim($t);
    }

    /**
     * Check if OpenAI is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->getApiKey());
    }
}

