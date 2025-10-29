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
                $sys = "You are an evaluator for BSIT internship journals.\n\nYour job is to:\n1. Correct and refine Activities and Learnings (grammar, punctuation, structure) without changing meaning.\n2. Produce a section-wide weekly summary in 2–3 sentences, third-person only (no I/me/we).\n3. Identify Program Outcomes (PO1–PO15) achieved (hit) and not achieved with brief reasons.\n4. Additionally, provide TWO distinct PO hit lists: (a) word-based hits (po_word_hit) when clear keywords/phrases appear; (b) context-based hits (po_context_hit) when the prose implies achievement even without explicit keywords.\nStart the summary with: 'In week {$weekLabel} the students ...'. Return STRICT JSON ONLY with keys: corrected_activities (array of strings), corrected_learnings (array of strings), 'summary for this section on a week' (string), pos_hit (array of {po, reason}), pos_not_hit (array of {po, reason}), po_word_hit (array of PO codes like ['PO1','PO3']), po_context_hit (array of PO codes like ['PO2']).";
                $usr = "Combined student reports for the week (cleaned):\n".$clean;
                $resp = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $sys],
                        ['role' => 'user', 'content' => $usr],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 220,
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
        $posHitExplanation = $this->formatPosExplanation('Explanation on the POs hit', $pos['hit']);
        $posNotHitExplanation = $this->formatPosExplanation('Explanation on the POs not hit', $pos['notHit']);

        return [
            'summary' => $summary,
            'usedGPT' => $usedGPT,
            'posHitExplanation' => $posHitExplanation,
            'posNotHitExplanation' => $posNotHitExplanation,
            'poWordHit' => $poTypes['word'],
            'poContextHit' => $poTypes['context'],
        ];
    }
}


