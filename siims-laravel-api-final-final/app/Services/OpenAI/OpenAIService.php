<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

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
    private const DEFAULT_TIMEOUT = 120; // Increased timeout to 2 minutes for better responses

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
        // Initialize request ID for tracking (used in all logs)
        $requestId = uniqid('req_', true);
        
        try {
            $apiKey = $this->getApiKey();
            
            if (!$apiKey) {
                Log::warning('OpenAI API key not configured');
                
                // Prepare log data for no API key
                $noApiKeyLogData = [
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                    'type' => 'prompt_request_no_api_key',
                    'error' => 'API key not configured',
                    'prompt' => null
                ];
                
                // Log simple message to Laravel log (no prompt details)
                Log::warning('OpenAI Prompt Request - API key not configured', [
                    'request_id' => $requestId
                ]);
                
                // Write full details to dedicated OpenAI JSON log file
                $this->writeToJsonLog($noApiKeyLogData);
                
                // Prepare log data for result error
                $resultErrorLogData = [
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                    'type' => 'prompt_result_error',
                    'success' => false,
                    'error' => [
                        'message' => 'API key not configured'
                    ]
                ];
                
                // Log simple error to Laravel log
                Log::error('OpenAI Prompt Result (Error) - API key not configured', [
                    'request_id' => $requestId
                ]);
                
                // Write full error details to dedicated OpenAI JSON log file
                $this->writeToJsonLog($resultErrorLogData);
                
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
            
            // Detect if this is a data-heavy prompt (PO, Summary, or Coordinator prompts)
            // These prompts contain large JSON/data that we don't want in Laravel logs
            $isDataHeavyPrompt = $this->isDataHeavyPrompt($normalizedMessages);
            
            // For data-heavy prompts, skip prompt logging entirely - only log results
            if (!$isDataHeavyPrompt) {
                // Prepare log data for prompt request
                $requestLogData = [
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                    'type' => 'prompt_request',
                    'prompt' => [
                        'model' => $config['model'],
                        'messages' => $normalizedMessages,
                        'temperature' => $config['temperature'],
                        'max_tokens' => $config['max_tokens'],
                        'top_p' => $config['top_p'] ?? 0.95,
                    ]
                ];
                
                // Log simple message to Laravel log (no prompt details)
                Log::info('OpenAI Prompt Request', [
                    'request_id' => $requestId,
                    'model' => $config['model'],
                    'messages_count' => count($normalizedMessages)
                ]);
                
                // Write full details to dedicated OpenAI JSON log file
                $this->writeToJsonLog($requestLogData);
            }

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
                
                // Prepare log data for prompt result (only OpenAI response data, no prompt)
                $resultLogData = [
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                    'type' => 'prompt_result',
                    'success' => true,
                    'result' => [
                        'model' => $data['model'] ?? null,
                        'content' => $content ? trim($content) : null,
                        'usage' => [
                            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                            'completion_tokens' => $usage['completion_tokens'] ?? 0,
                            'total_tokens' => $usage['total_tokens'] ?? 0,
                        ],
                        'response_id' => $data['id'] ?? null,
                        'created' => $data['created'] ?? null,
                        'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                        'object' => $data['object'] ?? null,
                    ]
                ];
                
                // Log summary result to Laravel log (no raw content)
                Log::info('OpenAI Prompt Result', [
                    'request_id' => $requestId,
                    'success' => true,
                    'model' => $data['model'] ?? null,
                    'content_length' => $content ? strlen($content) : 0,
                    'content_preview' => $content ? substr(trim($content), 0, 100) . '...' : null,
                    'usage' => [
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                        'completion_tokens' => $usage['completion_tokens'] ?? 0,
                        'total_tokens' => $usage['total_tokens'] ?? 0,
                    ],
                    'response_id' => $data['id'] ?? null,
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? null
                ]);
                
                // Write full details (including content) to dedicated OpenAI JSON log file
                $this->writeToJsonLog($resultLogData);

                return [
                    'success' => true,
                    'content' => $content ? trim($content) : null,
                    'error' => null,
                    'raw' => $data,
                    'usage' => $usage // Include token usage in response
                ];
            } else {
                $errorData = $response->json();
                
                // Prepare log data for error result (only error details, no prompt)
                $errorLogData = [
                    'request_id' => $requestId,
                    'timestamp' => now()->toIso8601String(),
                    'type' => 'prompt_result_error',
                    'success' => false,
                    'error' => [
                        'status' => $response->status(),
                        'message' => 'API call failed',
                        'error_details' => $errorData
                    ]
                ];
                
                // Log simple error to Laravel log
                Log::error('OpenAI Prompt Result (Error)', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'message' => 'API call failed'
                ]);
                
                // Write full error details to dedicated OpenAI JSON log file
                $this->writeToJsonLog($errorLogData);

                return [
                    'success' => false,
                    'content' => null,
                    'error' => 'API call failed',
                    'raw' => $errorData
                ];
            }
        } catch (\Exception $e) {
            // Prepare log data for exception result (only exception details, no prompt)
            $exceptionLogData = [
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
                'type' => 'prompt_result_exception',
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
            
            // Log simple exception to Laravel log
            Log::error('OpenAI Prompt Result (Exception)', [
                'request_id' => $requestId,
                'message' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            
            // Write full exception details to dedicated OpenAI JSON log file
            $this->writeToJsonLog($exceptionLogData);

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
        string $model = 'gpt-4o-mini',
        int $maxTokens = 500,
        float $temperature = 0.7,
        int $timeout = 90
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

    /**
     * Check if the prompt contains large data (PO, Summary, or Coordinator prompts)
     * These prompts include JSON/data that we don't want in Laravel logs
     *
     * @param array $messages Normalized messages array
     * @return bool
     */
    private function isDataHeavyPrompt(array $messages): bool
    {
        // Combine all message content to check
        $combinedContent = '';
        foreach ($messages as $message) {
            if (isset($message['content']) && is_string($message['content'])) {
                $combinedContent .= ' ' . strtolower($message['content']);
            }
        }
        
        // Check for PO-related keywords
        $poKeywords = [
            'program outcome',
            'program outcomes',
            'po1', 'po2', 'po3', 'po4', 'po5', 'po6', 'po7', 'po8', 'po9', 'po10',
            'po11', 'po12', 'po13', 'po14', 'po15',
            'pos_hit', 'pos_not_hit', 'po_hit', 'po_not_hit',
            'po_context_hit', 'po_word_hit',
            'identify program outcomes',
            'po analysis',
            'bsit internship evaluator'
        ];
        
        // Check for Summary-related keywords (Chairperson)
        $summaryKeywords = [
            'student data (json format',
            'json format for fast processing',
            'academic writing expert',
            'create a polished, professional summary',
            'internship program report',
            'weekly summary',
            'overall summary',
            'chairperson',
            'for overall, the students',
            'for this week, those students'
        ];
        
        // Check for Coordinator-related keywords
        $coordinatorKeywords = [
            'coordinator summary',
            'single student\'s internship journal',
            'source text (cleaned)',
            'for this week, the student',
            'coordinator',
            'po word hits',
            'contextual program outcome definitions'
        ];
        
        // Check for large JSON data patterns
        $hasLargeJsonData = preg_match('/"activities"\s*:|"learnings"\s*:|source text/i', $combinedContent);
        
        // Check PO keywords
        foreach ($poKeywords as $keyword) {
            if (strpos($combinedContent, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        // Check Summary keywords
        foreach ($summaryKeywords as $keyword) {
            if (strpos($combinedContent, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        // Check Coordinator keywords
        foreach ($coordinatorKeywords as $keyword) {
            if (strpos($combinedContent, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        // Check for large JSON/data patterns
        if ($hasLargeJsonData) {
            return true;
        }
        
        return false;
    }

    /**
     * Write log entry to dedicated OpenAI JSON log file
     * Each log entry is written as a single JSON line for easy parsing
     *
     * @param array $logData The log data to write
     * @return void
     */
    private function writeToJsonLog(array $logData): void
    {
        try {
            $logPath = storage_path('logs/openai.json');
            
            // Create logs directory if it doesn't exist
            $logDir = dirname($logPath);
            if (!File::exists($logDir)) {
                File::makeDirectory($logDir, 0755, true);
            }
            
            // Encode the log data as JSON (single line for easier parsing)
            // Each entry is on its own line, making it easy to parse line by line
            $jsonLine = json_encode($logData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            
            // Append to the log file
            File::append($logPath, $jsonLine);
        } catch (\Exception $e) {
            // If file writing fails, log the error but don't break the application
            Log::warning('Failed to write to OpenAI JSON log file', [
                'error' => $e->getMessage(),
                'log_data' => $logData
            ]);
        }
    }
}

