<?php

namespace App\Services\Chairperson;

/**
 * Chairperson Summary Prompt Builder
 * 
 * Centralized prompt building for chairperson summary generation (multiple students).
 * Moved from App\Services\OpenAI\PromptBuilder
 */
class ChairSummaryPromptBuilder
{
    /**
     * Build summary prompt based on type
     * OPTIMIZED: Convert activities and learnings to JSON for faster OpenAI processing
     *
     * @param array $activities
     * @param array $learnings
     * @param string $assessment
     * @param string $type
     * @return array System and user messages for OpenAI
     */
    public function buildSummaryPrompt(array $activities, array $learnings, string $assessment, string $type): array
    {
        // OPTIMIZATION: Convert to JSON format for faster processing
        // Use compact JSON (no pretty print) to reduce token count
        $dataJson = [
            'activities' => is_array($activities) ? $activities : (!empty($activities) ? [$activities] : []),
            'learnings' => is_array($learnings) ? $learnings : (!empty($learnings) ? [$learnings] : []),
            'assessment' => $assessment,
            'total_activities' => is_array($activities) ? count($activities) : (!empty($activities) ? 1 : 0),
            'total_learnings' => is_array($learnings) ? count($learnings) : (!empty($learnings) ? 1 : 0)
        ];
        
        $jsonData = json_encode($dataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Chairperson overall (backward-compatible key overall_summary)
        if ($type === 'chair_overall' || $type === 'overall_summary') {
            return $this->buildOverallPrompt($jsonData, $assessment);
        }

        // Chairperson weekly (group of students)
        if ($type === 'chair_week') {
            return $this->buildChairWeeklyPrompt($jsonData, $assessment);
        }

        // Default to chairperson weekly if type is not recognized
        return $this->buildChairWeeklyPrompt($jsonData, $assessment);
    }

    /**
     * Build overall summary prompt
     * OPTIMIZED: Uses JSON format for faster processing
     */
    private function buildOverallPrompt(string $jsonData, string $assessment): array
    {
        $system = "You are an academic writing expert. Create a polished, professional summary for an internship program report.";
        
        $user = "STUDENT DATA (JSON FORMAT): {$jsonData}

TASK: Parse the JSON to extract activities and learnings, then create a summary.

REQUIREMENTS:
1. Begin EXACTLY with: 'For overall, the students '
2. Write EXCLUSIVELY in third person (students, they, their, them) - NEVER first person (I, me, my, we, us, our)
3. Convert first-person to third person: 'I learned' → 'students learned', 'I gained' → 'students gained', etc.
4. Use sophisticated vocabulary, complex sentence structures, and varied syntax (simple, compound, complex)
5. Create logical connections between activities and outcomes using transitional phrases
6. Write as a single, well-crafted paragraph with perfect grammar and flow
7. Include specific details, use active voice, and end with a strong conclusion

Generate a polished, professional academic paragraph.";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ];
    }

    /**
     * Build chairperson weekly prompt
     * OPTIMIZED: Uses JSON format for faster processing
     */
    private function buildChairWeeklyPrompt(string $jsonData, string $assessment): array
    {
        $system = "You are an academic writing expert. Create a polished, professional weekly summary for an internship program report.";
        
        $user = "STUDENT DATA (JSON FORMAT): {$jsonData}

TASK: Parse the JSON to extract activities and learnings, then create a summary.

REQUIREMENTS:
1. Begin EXACTLY with: 'For this week, those students '
2. Write EXCLUSIVELY in third person (students, they, their, them) - NEVER first person (I, me, my, we, us, our)
3. Convert list-like fragments into fluent sentences; avoid repeating labels like 'activities' or 'learnings'
4. Produce 2–3 coherent sentences that synthesize ACTIVITIES and LEARNING OUTCOMES into a narrative
5. Use perfect grammar, academic tone, transitional phrases, and logical flow
6. Include specific details, use active voice, and end with a strong conclusion
7. Avoid redundancy and do not echo inputs verbatim

Generate a polished, professional academic paragraph.";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ];
    }

}

