<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAISummaryController extends Controller
{
    public function test()
    {
        $user = auth()->user();
        return response()->json([
            'status' => 'OpenAI controller is working',
            'timestamp' => now(),
            'openai_configured' => !empty(config('services.openai.api_key')),
            'user_authenticated' => !!$user,
            'user_id' => $user ? $user->id : null,
            'user_roles' => $user ? $user->roles->pluck('name')->toArray() : [],
            'user_email' => $user ? $user->email : null
        ]);
    }
    
    public function summarize(Request $request)
    {
        try {
            $data = $request->input('data');
            $type = $request->input('type', 'overall_summary');
            
            Log::info('OpenAI Summarization Request', [
                'type' => $type,
                'data_keys' => array_keys($data ?? [])
            ]);
            
            if (!$data) {
                return response()->json(['error' => 'No data provided'], 400);
            }
            
            // Extract and clean structured data
            $activities = $data['corrected_activities'] ?? [];
            $learnings = $data['corrected_learnings'] ?? [];
            $assessment = $data['summary for this section on a week'] ?? '';
            
            // Normalize arrays if they're strings
            if (is_string($activities)) {
                $activities = json_decode($activities, true) ?? [];
            }
            if (is_string($learnings)) {
                $learnings = json_decode($learnings, true) ?? [];
            }
            
            // Clean up the data - remove quotes and normalize
            $activities = $this->cleanDataArray($activities);
            $learnings = $this->cleanDataArray($learnings);
            $assessment = $this->cleanText($assessment);
            
            Log::info('Cleaned data for OpenAI', [
                'activities' => $activities,
                'learnings' => $learnings,
                'assessment' => $assessment
            ]);
            
            // Create a comprehensive prompt for OpenAI
            $prompt = $this->createPrompt($activities, $learnings, $assessment, $type);
            
            // Call OpenAI API
            $response = $this->callOpenAI($prompt);
            
            if ($response['success']) {
                // Clean the OpenAI response
                $cleanSummary = $this->cleanText($response['summary']);
                if ($type !== 'overall_summary') {
                    $cleanSummary = $this->enforceWeekPrefix($cleanSummary);
                }
                
                Log::info('OpenAI generated summary', ['summary' => $cleanSummary]);
                
                return response()->json([
                    'summary' => $cleanSummary,
                    'success' => true
                ]);
            } else {
                Log::warning('OpenAI failed, using fallback', ['error' => $response['error'] ?? 'Unknown error']);
                
                $fallbackSummary = $this->generateFallbackSummary($activities, $learnings, $assessment);
                if ($type !== 'overall_summary') {
                    $fallbackSummary = $this->enforceWeekPrefix($fallbackSummary);
                }
                
                return response()->json([
                    'summary' => $fallbackSummary,
                    'success' => true,
                    'fallback' => true
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('OpenAI Summarization Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            // Try to provide a fallback summary even on error
            try {
                $fallbackSummary = $this->generateFallbackSummary(
                    $data['corrected_activities'] ?? [],
                    $data['corrected_learnings'] ?? [],
                    $data['summary for this section on a week'] ?? ''
                );
                if (($request->input('type') ?? 'overall_summary') !== 'overall_summary') {
                    $fallbackSummary = $this->enforceWeekPrefix($fallbackSummary);
                }
                
                return response()->json([
                    'summary' => $fallbackSummary,
                    'success' => true,
                    'fallback' => true,
                    'error' => 'OpenAI unavailable, using fallback'
                ]);
            } catch (\Exception $fallbackError) {
                Log::error('Fallback summary also failed', [
                    'error' => $fallbackError->getMessage()
                ]);
                
                return response()->json([
                    'error' => 'Internal server error',
                    'message' => 'Unable to generate summary at this time'
                ], 500);
            }
        }
    }
    
    private function createPrompt($activities, $learnings, $assessment, $type)
    {
        $activitiesText = is_array($activities) ? implode(', ', $activities) : $activities;
        $learningsText = is_array($learnings) ? implode(', ', $learnings) : $learnings;
        
        // Chairperson overall (backward-compatible key overall_summary)
        if ($type === 'chair_overall' || $type === 'overall_summary') {
            return "You are an academic writing expert. Create a polished, professional summary for an internship program report.

STUDENT ACTIVITIES: {$activitiesText}

LEARNING OUTCOMES: {$learningsText}

ASSESSMENT: {$assessment}

WRITING REQUIREMENTS:
1. Begin with 'For overall, '
2. Write EXCLUSIVELY in third person (students, they, their, them) - NEVER use first person (I, me, my, we, us, our)
3. Use sophisticated vocabulary and complex sentence structures
4. Ensure perfect grammar, punctuation, and sentence flow
5. Create logical connections between activities and outcomes
6. Use transitional phrases for smooth flow
7. Maintain professional, formal language throughout
8. Write as a single, well-crafted paragraph
9. Avoid repetition and redundancy
10. Use varied sentence structures (simple, compound, complex)

CRITICAL: Convert any first-person language to third person:
- 'I learned' → 'students learned' or 'they learned'
- 'I was able to' → 'students were able to' or 'they were able to'
- 'I became' → 'students became' or 'they became'
- 'I gained' → 'students gained' or 'they gained'
- 'I developed' → 'students developed' or 'they developed'

STYLE GUIDELINES:
- Use active voice where appropriate
- Include specific details from the data
- Create a narrative that flows logically
- Use academic connectors (furthermore, moreover, consequently, etc.)
- Ensure each sentence builds upon the previous one
- End with a strong concluding statement

Generate a single, polished paragraph that reads like professional academic writing.";
        }

        // Chairperson weekly (group of students)
        if ($type === 'chair_week') {
            return "You are an academic writing expert. Create a polished, professional weekly summary for an internship program report.

STUDENT ACTIVITIES: {$activitiesText}

LEARNING OUTCOMES: {$learningsText}

ASSESSMENT: {$assessment}

 WRITING REQUIREMENTS:
1. Begin EXACTLY with: 'For this week, those students '
2. Write EXCLUSIVELY in third person (students, they, their, them) — NEVER use first person (I, me, my, we, us, our)
3. Convert list-like fragments into fluent sentences; avoid repeating labels like 'activities' or 'learnings'
4. Produce 2–3 coherent sentences that synthesize ACTIVITIES and LEARNING OUTCOMES into a narrative
5. Ensure perfect grammar, punctuation, and sentence flow with academic tone
6. Create logical connections between activities and outcomes using transitional phrases
7. Maintain professional, formal language throughout
8. Avoid redundancy and do not echo the inputs verbatim

STYLE GUIDELINES:
- Use active voice where appropriate
- Include specific details from the data
- Create a narrative that flows logically
- Use academic connectors (furthermore, moreover, consequently, etc.)
- Ensure each sentence builds upon the previous one
- End with a strong concluding statement

Generate a single, polished paragraph that reads like professional academic writing.";
        }

        // Coordinator weekly (single student)
        if ($type === 'coordinator_week') {
            return "You are an academic writing expert. Create a polished, professional weekly summary for an internship program report.

STUDENT ACTIVITIES: {$activitiesText}

LEARNING OUTCOMES: {$learningsText}

ASSESSMENT: {$assessment}

 WRITING REQUIREMENTS:
1. Begin EXACTLY with: 'For this week, the student '
2. Write EXCLUSIVELY in third person (the student, they, their) — NEVER use first person (I, me, my, we, us, our)
3. Convert list-like fragments into fluent sentences; avoid repeating labels like 'activities' or 'learnings'
4. Produce 2–3 coherent sentences that synthesize ACTIVITIES and LEARNING OUTCOMES into a narrative
5. Ensure perfect grammar, punctuation, and sentence flow with academic tone
6. Create logical connections between activities and outcomes using transitional phrases
7. Maintain professional, formal language throughout
8. Avoid redundancy and do not echo the inputs verbatim

STYLE GUIDELINES:
- Use active voice where appropriate
- Include specific details from the data
- Create a narrative that flows logically
- Use academic connectors (furthermore, moreover, consequently, etc.)
- Ensure each sentence builds upon the previous one
- End with a strong concluding statement

Generate a single, polished paragraph that reads like professional academic writing.";
        }

        // Default generic weekly
        return "You are an academic writing expert. Create a polished, professional weekly summary for an internship program report.

STUDENT ACTIVITIES: {$activitiesText}

LEARNING OUTCOMES: {$learningsText}

ASSESSMENT: {$assessment}

 WRITING REQUIREMENTS:
1. Begin EXACTLY with: 'For this week, those students '
2. Write EXCLUSIVELY in third person (students, they, their, them) — NEVER use first person (I, me, my, we, us, our)
3. Convert list-like fragments into fluent sentences; avoid repeating labels like 'activities' or 'learnings'
4. Produce 2–3 coherent sentences that synthesize ACTIVITIES and LEARNING OUTCOMES into a narrative
5. Ensure perfect grammar, punctuation, and sentence flow with academic tone
6. Create logical connections between activities and outcomes using transitional phrases
7. Maintain professional, formal language throughout
8. Avoid redundancy and do not echo the inputs verbatim

STYLE GUIDELINES:
- Use active voice where appropriate
- Include specific details from the data
- Create a narrative that flows logically
- Use academic connectors (furthermore, moreover, consequently, etc.)
- Ensure each sentence builds upon the previous one
- End with a strong concluding statement

Generate a single, polished paragraph that reads like professional academic writing.";
    }
    
    private function callOpenAI($prompt)
    {
        try {
            $apiKey = config('services.openai.api_key');
            
            if (!$apiKey) {
                Log::warning('OpenAI API key not configured');
                return ['success' => false, 'error' => 'API key not configured'];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7
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
    
    private function generateFallbackSummary($activities, $learnings, $assessment)
    {
        $activitiesText = is_array($activities) ? implode(', ', $activities) : $activities;
        $learningsText = is_array($learnings) ? implode(', ', $learnings) : $learnings;
        
        $summary = "For overall, ";
        
        if (!empty($activitiesText)) {
            $summary .= "students demonstrated comprehensive engagement in various professional activities, including {$activitiesText}. ";
        }
        
        if (!empty($learningsText)) {
            $summary .= "Through these hands-on experiences, they achieved significant professional development and skill acquisition in {$learningsText}. ";
        }
        
        if (!empty($assessment)) {
            $summary .= "Furthermore, {$assessment}";
        }
        
        // Clean up any double spaces and ensure proper punctuation
        $summary = preg_replace('/\s+/', ' ', trim($summary));
        if (!preg_match('/[.!?]$/', $summary)) {
            $summary .= '.';
        }
        
        return $summary;
    }

    private function enforceWeekPrefix(string $text): string
    {
        $t = trim($text);
        if ($t === '') return 'For this week, those students completed their weekly activities and learning outcomes.';
        // If it already starts with the desired phrase, return as is
        if (preg_match('/^For\s+this\s+week,\s+those\s+students/i', $t)) {
            return $t;
        }
        // Remove any leading connectors like "In week", "This week," etc.
        $t = preg_replace('/^(In\s+week\s+\d+\s*,\s*|This\s+week\s*,\s*|In\s+this\s+week\s*,\s*)/i', '', $t);
        return 'For this week, those students ' . ltrim($t);
    }
    
    private function cleanDataArray($data)
    {
        if (!is_array($data)) {
            return [];
        }
        
        return array_map(function($item) {
            return $this->cleanText($item);
        }, $data);
    }
    
    private function cleanText($text)
    {
        if (!is_string($text)) {
            return $text;
        }
        
        return trim(
            preg_replace('/\s+/', ' ', // Normalize whitespace
                str_replace(['"', "'", '\\"', "\\'"], '', $text) // Remove quotes
            )
        );
    }
}
