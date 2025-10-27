<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoordinatorOpenAISummaryController extends Controller
{
    public function summarize(Request $request)
    {
        try {
            $data = $request->input('data');
            if (is_string($data)) {
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $decoded;
                }
            }
            // Also support GET with base64/json in query param
            if (!$data && $request->query('data')) {
                $raw = $request->query('data');
                $raw = is_string($raw) ? urldecode($raw) : $raw;
                $tryJson = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $tryJson;
                } else {
                    $tryJson = json_decode(base64_decode($raw), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data = $tryJson;
                    }
                }
            }
            if (!$data) {
                return response()->json(['error' => 'No data provided'], 400);
            }

            $activities = $data['corrected_activities'] ?? [];
            $learnings = $data['corrected_learnings'] ?? [];
            $assessment = $data['summary for this section on a week'] ?? '';

            if (is_string($activities)) { $activities = json_decode($activities, true) ?? []; }
            if (is_string($learnings)) { $learnings = json_decode($learnings, true) ?? []; }

            $activities = $this->cleanDataArray($activities);
            $learnings = $this->cleanDataArray($learnings);
            $assessment = $this->cleanText($assessment);

            // Build PO word-hit logic and contextual mapping
            $text = trim(implode(' ', $learnings) . ' ' . implode(' ', $activities));
            $lower = mb_strtolower($text);
            $poWordMap = [
                'PO1' => ['apply', 'compute', 'calculate', 'solve', 'use knowledge'],
                'PO2' => ['standard', 'best practice', 'quality', 'performance', 'requirement'],
                'PO3' => ['analyze', 'troubleshoot', 'test', 'debug', 'identify', 'evaluate'],
                'PO4' => ['user need', 'requirement analysis', 'evaluation', 'feedback'],
                'PO5' => ['design', 'develop', 'implement', 'create', 'build', 'deploy'],
                'PO6' => ['integrate', 'adapt', 'maintain', 'environment', 'safety', 'sustainability'],
                'PO7' => ['tool', 'modern technology', 'programming', 'configure', 'software'],
                'PO8' => ['team', 'collaborate', 'assist', 'coordinate', 'leader'],
                'PO9' => ['plan', 'project plan', 'timeline', 'documentation', 'scheduling'],
                'PO10' => ['communicate', 'present', 'report', 'explain', 'document'],
                'PO11' => ['impact', 'society', 'organization', 'community', 'global'],
                'PO12' => ['ethics', 'privacy', 'law', 'responsibility', 'security', 'professionalism'],
                'PO13' => ['learn independently', 'explore', 'research', 'self-study', 'improve skills'],
                'PO14' => ['research', 'innovation', 'development', 'contribution', 'national goal'],
                'PO15' => ['filipino', 'culture', 'heritage', 'values'],
            ];
            $poDefinitions = [
                'PO1' => 'Apply knowledge of computing, science, and mathematics in solving computing/IT-related problems through critical and creative thinking.',
                'PO2' => 'Use current best practices and standards in solving complex computing/IT-related problems and requirements.',
                'PO3' => 'Analyze complex computing/IT-related problems by applying analytical and quantitative reasoning, and define the computing requirements appropriate to its solution.',
                'PO4' => 'Identify and analyze user needs and take them into account in the selection, creation, evaluation, and administration of computer-based systems.',
                'PO5' => 'Design creatively, implement, and evaluate different computer-based systems, processes, components, or programs to meet desired needs and requirements under various constraints.',
                'PO6' => 'Integrate effectively the IT-based solutions into the user environment with appropriate consideration for public health and safety, cultural, societal, and environmental concerns.',
                'PO7' => 'Select, adapt, and apply appropriate techniques, resources, skills, and modern computing tools to complex computing activities, with an understanding of the limitations.',
                'PO8' => 'Function effectively as an individual, or work collaboratively and respectfully as a member or leader in diverse development teams and in multidisciplinary and/or multicultural settings.',
                'PO9' => 'Assist in the creation of an effective IT project plan.',
                'PO10' => 'Communicate effectively in both oral and written form and present persuasively complex computing/IT-related ideas.',
                'PO11' => 'Assess local and global impact of computing and IT on individuals, organizations, and society.',
                'PO12' => 'Act in recognition of professional, ethical, legal, security, and social responsibilities.',
                'PO13' => 'Recognize the need to engage in independent learning and stay updated with the latest developments in specialized IT fields for continual professional development.',
                'PO14' => 'Participate in generation of new knowledge or in R&D projects aligned with local and national development agendas.',
                'PO15' => 'Preserve and promote Filipino historical and cultural heritage.',
            ];
            $hits = [];
            foreach ($poWordMap as $po => $words) {
                $count = 0; $matched = [];
                foreach ($words as $w) {
                    $needle = mb_strtolower($w);
                    if (str_contains($lower, $needle)) { $count++; $matched[] = $w; }
                }
                if ($count > 0) { $hits[$po] = ['count' => $count, 'matched' => $matched]; }
            }

            // Compose prompt based on hits or contextual mapping
            $intro = "You are an academic writing expert. Write a polished, professional weekly summary for a single student's internship journal. Always write in third person and do not use first-person words.";
            $reqs = "Begin EXACTLY with: 'For this week, the student '. Produce 2–3 coherent sentences that combine ACTIVITIES and LEARNINGS. Do NOT list or mention PO codes in the summary. Ensure excellent grammar and flow.";
            $source = "SOURCE TEXT (cleaned): \n". $text ."\n";
            if (!empty($hits)) {
                $hitLines = [];
                foreach ($hits as $po => $info) {
                    $hitLines[] = $po . ' => words: ' . implode(', ', $info['matched']);
                }
                $hitSection = "PO WORD HITS (synonym match):\n" . implode("\n", $hitLines) . "\n";
                $prompt = $intro."\n".$reqs."\n".$source.$hitSection.
                    "TASK: Use the PO WORD HITS only to understand the context, but DO NOT mention PO codes in the summary. Return only the summary paragraph.";
            } else {
                $defs = [];
                foreach ($poDefinitions as $po => $def) { $defs[] = $po.': '.$def; }
                $defSection = "CONTEXTUAL PROGRAM OUTCOME DEFINITIONS:\n" . implode("\n", $defs) . "\n";
                $prompt = $intro."\n".$reqs."\n".$source.$defSection.
                    "TASK: No direct PO words matched. Use the definitions only for context; DO NOT mention PO codes. Return only the summary paragraph.";
            }

            $response = $this->callOpenAI($prompt);
            if ($response['success']) {
                $clean = $this->cleanText($response['summary']);
                // Ensure intro
                if (!preg_match('/^For\s+this\s+week,\s+the\s+student/i', $clean)) {
                    $clean = 'For this week, the student ' . ltrim($clean);
                }
                return response()->json(['summary' => $clean, 'success' => true]);
            }

            // Fallback paragraph
            $summary = $this->generateFallbackSummary($activities, $learnings, $assessment);
            if (!preg_match('/^For\s+this\s+week,\s+the\s+student/i', $summary)) {
                $summary = 'For this week, the student ' . ltrim($summary);
            }
            return response()->json(['summary' => $summary, 'success' => true, 'fallback' => true]);

        } catch (\Throwable $e) {
            Log::error('Coordinator OpenAI Summarization Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    private function createCoordinatorPrompt($activities, $learnings, $assessment)
    {
        $activitiesText = is_array($activities) ? implode(', ', $activities) : $activities;
        $learningsText = is_array($learnings) ? implode(', ', $learnings) : $learnings;
        return "You are an academic writing expert. Create a polished, professional weekly summary for a single student's internship report.

STUDENT ACTIVITIES: {$activitiesText}

LEARNING OUTCOMES: {$learningsText}

ASSESSMENT: {$assessment}

WRITING REQUIREMENTS:
1. Begin EXACTLY with: 'For this week, the student '
2. Write EXCLUSIVELY in third person (the student, they, their) — NEVER use first person (I, me, my, we, us, our)
3. Convert list-like fragments into fluent sentences; avoid repeating labels like 'activities' or 'learnings'
4. Produce 2–3 coherent sentences that synthesize ACTIVITIES and LEARNING OUTCOMES into a single narrative
5. Ensure perfect grammar, punctuation, and sentence flow with academic tone
6. Use transitional phrases (furthermore, moreover, consequently, etc.) to connect ideas
7. Avoid redundancy and do not echo the inputs verbatim

Generate a single, polished paragraph that reads like professional academic writing.";
    }

    private function callOpenAI($prompt)
    {
        try {
            $apiKey = config('services.openai.api_key');
            if (!$apiKey) {
                return ['success' => false, 'error' => 'API key not configured'];
            }
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 300,
                'temperature' => 0.6,
            ]);
            if ($resp->successful()) {
                $data = $resp->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                return ['success' => !empty($content), 'summary' => trim((string)$content)];
            }
            return ['success' => false, 'error' => 'API call failed'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function generateFallbackSummary($activities, $learnings, $assessment)
    {
        $activitiesText = is_array($activities) ? implode(', ', $activities) : $activities;
        $learningsText = is_array($learnings) ? implode(', ', $learnings) : $learnings;
        $summary = '';
        if ($activitiesText) {
            $summary .= "completed activities such as {$activitiesText}. ";
        }
        if ($learningsText) {
            $summary .= "They demonstrated learning in {$learningsText}. ";
        }
        if ($assessment) {
            $summary .= $assessment;
        }
        return trim(preg_replace('/\s+/', ' ', $summary));
    }

    private function cleanDataArray($data)
    {
        if (!is_array($data)) return [];
        return array_map(fn($t) => $this->cleanText($t), $data);
    }

    private function cleanText($text)
    {
        if (!is_string($text)) return '';
        return trim(preg_replace('/\s+/', ' ', str_replace(['"', "'", '\\"', "\\'"], '', $text)));
    }
}


