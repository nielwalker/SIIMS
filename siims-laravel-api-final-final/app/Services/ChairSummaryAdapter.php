<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChairSummaryAdapter
{
    private function normalizeSummary(?string $raw): string
    {
        if (!$raw) return '';
        $content = trim((string)$raw);
        // remove ```json fences
        $content = preg_replace_callback('/```json[\s\S]*?```/i', function ($m) {
            return preg_replace('/```json|```/i', '', $m[0]);
        }, $content) ?? $content;
        // try JSON
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $v = $decoded['summary for this section on a week'] ?? ($decoded['summary'] ?? ($decoded['result'] ?? null));
            if (is_string($v) && trim($v) !== '') return trim($v);
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
        if (!$raw) return ['hit' => $hit, 'notHit' => $notHit];
        $content = (string)$raw;
        $content = preg_replace_callback('/```json[\s\S]*?```/i', function ($m) {
            return preg_replace('/```json|```/i', '', $m[0]);
        }, $content) ?? $content;
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $hit = is_array($decoded['pos_hit'] ?? null) ? $decoded['pos_hit'] : [];
            $notHit = is_array($decoded['pos_not_hit'] ?? null) ? $decoded['pos_not_hit'] : [];
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
        $lines = array_map(function ($it) {
            $po = is_string($it['po'] ?? null) ? $it['po'] : (string)($it['po'] ?? '');
            $reason = is_string($it['reason'] ?? null) ? $it['reason'] : '';
            return trim($po.' – '.$reason);
        }, $items);
        return $title.': '.implode('; ', $lines);
    }

    public function summarize(string $text, ?int $week, bool $useGPT = false): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        $summary = $clean ?: 'No journal entries found.';
        $usedGPT = false;
        $rawContent = null;

        $apiKey = env('OPENAI_API_KEY');
        if ($useGPT && $apiKey && $clean) {
            try {
                $weekLabel = $week ? (string)$week : 'the selected week';
                
                // Comprehensive PO descriptions for context understanding
                $poDescriptions = [
                    'PO1' => 'Apply knowledge of computing, science, and mathematics appropriate to the discipline.',
                    'PO2' => 'Analyze a complex computing problem and apply principles of computing and other relevant disciplines to identify solutions.',
                    'PO3' => 'Design, implement, and evaluate a computing-based solution to meet a given set of computing requirements in the context of the program\'s discipline.',
                    'PO4' => 'Use current techniques, skills, and tools necessary for computing practice.',
                    'PO5' => 'Function effectively as a member or leader of a team engaged in activities appropriate to the program\'s discipline.',
                    'PO6' => 'Communicate effectively with a range of audiences.',
                    'PO7' => 'Analyze the local and global impact of computing on individuals, organizations, and society.',
                    'PO8' => 'Recognize professional responsibilities and make informed judgments in computing practice based on legal and ethical principles.',
                    'PO9' => 'Function effectively on teams to accomplish a common goal.',
                    'PO10' => 'Identify and analyze user needs and take them into account in the selection, creation, evaluation, and administration of computer-based systems.',
                    'PO11' => 'Design and develop computing solutions that integrate computing and non-computing requirements.',
                    'PO12' => 'Apply appropriate techniques and tools for the specification, design, implementation, and testing of computer systems.',
                    'PO13' => 'Recognize the need for and engage in continuing professional development.',
                    'PO14' => 'Contribute effectively to the development of computing solutions in a team environment.',
                    'PO15' => 'Demonstrate understanding of Filipino culture, values, and heritage in the context of computing solutions.'
                ];
                
                $sys = "You are an expert evaluator for BSIT (Bachelor of Science in Information Technology) internship journals. Your role is to analyze student internship reports and assess Program Outcomes (POs) achievement.

PROGRAM OUTCOMES (PO1-PO15) CONTEXT:
".implode("\n", array_map(function($code, $desc) {
    return "{$code}: {$desc}";
}, array_keys($poDescriptions), $poDescriptions))."

YOUR TASKS:
1. Correct and refine Activities and Learnings (improve grammar, punctuation, structure) without changing meaning.
2. Produce a section-wide weekly summary (2-3 sentences) in THIRD-PERSON ONLY (use: students, they, their, them - NEVER use I, me, we, us, our).
3. Identify Program Outcomes achieved (pos_hit) and NOT achieved (pos_not_hit) with specific, contextual reasons based on the PO descriptions above.
4. Provide TWO distinct PO hit classifications:
   - po_word_hit: Array of PO codes where explicit keywords/phrases from PO descriptions appear (e.g., ['PO1','PO4'])
   - po_context_hit: Array of PO codes where achievement is implied through context even without explicit keywords (e.g., ['PO5','PO6'])
5. Generate realistic, actionable RECOMMENDATIONS for improvement (recommendations) based on:
   - POs that were NOT achieved (pos_not_hit)
   - Patterns observed in student reports
   - Areas needing more emphasis for future improvement
   - Write recommendations in THIRD-PERSON (e.g., 'It is recommended that students focus on...' or 'The program should consider...')
   - Make recommendations specific, practical, and relevant to BSIT internship context
   - Provide 3-5 realistic recommendations

CRITICAL REQUIREMENTS:
- Understand the FULL CONTEXT of each PO before assigning it
- For po_context_hit: Infer PO achievement from descriptions, actions, and results even if exact keywords are missing
- For recommendations: Generate thoughtful, professional suggestions that sound like real academic recommendations
- NEVER use first-person language in summary or recommendations
- Start the summary with: 'In week {$weekLabel}, the students...'

Return STRICT VALID JSON ONLY with these keys:
- corrected_activities: array of strings
- corrected_learnings: array of strings  
- 'summary for this section on a week': string
- pos_hit: array of {po: string, reason: string}
- pos_not_hit: array of {po: string, reason: string}
- po_word_hit: array of PO codes like ['PO1','PO3']
- po_context_hit: array of PO codes like ['PO2','PO5']
- recommendations: array of strings (3-5 realistic, third-person recommendations)";
                
                $usr = "Combined student internship reports for the week (cleaned):\n".$clean;
                
                $resp = Http::withToken($apiKey)->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $sys],
                        ['role' => 'user', 'content' => $usr],
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 2000, // Increased for better recommendations
                ]);
                
                if ($resp->ok()) {
                    $data = $resp->json();
                    $content = $data['choices'][0]['message']['content'] ?? null;
                    if ($content) { 
                        $rawContent = $content; 
                        $summary = $this->normalizeSummary($content); 
                        $usedGPT = $summary !== '';
                    }
                }
            } catch (\Throwable $e) {
                // fallback to cleaned text summary
            }
        }

        if (!$usedGPT) {
            // simple fallback paragraph
            if ($clean) {
                $parts = array_values(array_filter(array_map('trim', preg_split('/[.!?]+/', $clean) ?: [])));
                $take = array_slice($parts, 0, min(3, count($parts)));
                if (!empty($take)) $summary = implode('. ', $take).'.';
            }
        }

        $pos = $this->extractPosArrays($rawContent);
        $poTypes = $this->extractPoHitTypes($rawContent);
        $recommendations = $this->extractRecommendations($rawContent);
        $posHitExplanation = $this->formatPosExplanation('Explanation on the POs hit', $pos['hit']);
        $posNotHitExplanation = $this->formatPosExplanation('Explanation on the POs not hit', $pos['notHit']);

        return [
            'summary' => $summary,
            'usedGPT' => $usedGPT,
            'posHitExplanation' => $posHitExplanation,
            'posNotHitExplanation' => $posNotHitExplanation,
            'poWordHit' => $poTypes['word'],
            'poContextHit' => $poTypes['context'],
            'recommendations' => $recommendations,
        ];
    }
}


