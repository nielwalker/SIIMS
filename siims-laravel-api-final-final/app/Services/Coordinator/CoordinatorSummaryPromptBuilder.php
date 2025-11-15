<?php

namespace App\Services\Coordinator;

/**
 * Coordinator Summary Prompt Builder
 * 
 * Builds prompts for coordinator summary generation (single student).
 * Focuses exclusively on summary generation - PO analysis is handled by CoordinatorPOPromptBuilder.
 */
class CoordinatorSummaryPromptBuilder
{
    /**
     * Build prompt for coordinator summary
     * OPTIMIZED: Convert activities and learnings to JSON for faster OpenAI processing
     *
     * @param array $activities
     * @param array $learnings
     * @param string $assessment
     * @param string $type Optional type (coordinator_overall, overall_summary, or coordinator_week)
     * @return array System and user messages for OpenAI
     */
    public function buildPrompt(array $activities, array $learnings, string $assessment, string $type = 'coordinator_week'): array
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
        
        // Coordinator overall (backward-compatible key overall_summary)
        if ($type === 'coordinator_overall' || $type === 'overall_summary') {
            return $this->buildCoordinatorOverallPrompt($jsonData, $assessment);
        }

        // Coordinator weekly (single student)
        if ($type === 'coordinator_week') {
            return $this->buildCoordinatorWeeklyPrompt($jsonData, $assessment);
        }

        // Default to coordinator weekly if type is not recognized
        return $this->buildCoordinatorWeeklyPrompt($jsonData, $assessment);
    }
    
    /**
     * Build coordinator overall summary prompt
     * OPTIMIZED: Uses JSON format for faster processing
     */
    private function buildCoordinatorOverallPrompt(string $jsonData, string $assessment): array
    {
        $system = "You are an academic writing expert. Create a polished, professional summary for an internship program report.";
        
        $user = "STUDENT DATA (JSON FORMAT): {$jsonData}

TASK: Parse the JSON to extract activities and learnings, then create a summary.

REQUIREMENTS:
1. Begin EXACTLY with: 'For overall, the student '
2. Write EXCLUSIVELY in third person (the student, they, their) - NEVER first person (I, me, my, we, us, our)
3. Convert first-person to third person: 'I learned' → 'the student learned', 'I gained' → 'the student gained', etc.
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
     * Build coordinator weekly prompt
     * OPTIMIZED: Uses JSON format for faster processing
     */
    private function buildCoordinatorWeeklyPrompt(string $jsonData, string $assessment): array
    {
        $system = "You are an academic writing expert. Create a polished, professional weekly summary for a single student's internship journal.";
        
        $user = "STUDENT DATA (JSON FORMAT): {$jsonData}

TASK: Parse the JSON to extract activities and learnings, then create a summary.

REQUIREMENTS:
1. Begin EXACTLY with: 'For this week, the student '
2. Write EXCLUSIVELY in third person (the student, they, their) - NEVER first person (I, me, my, we, us, our)
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

