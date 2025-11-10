<?php

namespace App\Services\OpenAI;

/**
 * Prompt Builder Service
 * 
 * Centralized prompt building for different summary types.
 * Consolidates duplicate prompt creation logic.
 */
class PromptBuilder
{
    /**
     * Build summary prompt based on type
     *
     * @param array $activities
     * @param array $learnings
     * @param string $assessment
     * @param string $type
     * @return array System and user messages for OpenAI
     */
    public function buildSummaryPrompt(array $activities, array $learnings, string $assessment, string $type): array
    {
        $activitiesText = is_array($activities) ? implode(', ', $activities) : $activities;
        $learningsText = is_array($learnings) ? implode(', ', $learnings) : $learnings;
        
        // Chairperson overall (backward-compatible key overall_summary)
        if ($type === 'chair_overall' || $type === 'overall_summary') {
            return $this->buildOverallPrompt($activitiesText, $learningsText, $assessment);
        }

        // Chairperson weekly (group of students)
        if ($type === 'chair_week') {
            return $this->buildChairWeeklyPrompt($activitiesText, $learningsText, $assessment);
        }

        // Coordinator weekly (single student)
        if ($type === 'coordinator_week') {
            return $this->buildCoordinatorWeeklyPrompt($activitiesText, $learningsText, $assessment);
        }

        // Default generic weekly
        return $this->buildDefaultWeeklyPrompt($activitiesText, $learningsText, $assessment);
    }

    /**
     * Build overall summary prompt
     */
    private function buildOverallPrompt(string $activitiesText, string $learningsText, string $assessment): array
    {
        $system = "You are an academic writing expert. Create a polished, professional summary for an internship program report.";
        
        $user = "STUDENT ACTIVITIES: {$activitiesText}

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

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ];
    }

    /**
     * Build chairperson weekly prompt
     */
    private function buildChairWeeklyPrompt(string $activitiesText, string $learningsText, string $assessment): array
    {
        $system = "You are an academic writing expert. Create a polished, professional weekly summary for an internship program report.";
        
        $user = "STUDENT ACTIVITIES: {$activitiesText}

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

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ];
    }

    /**
     * Build coordinator weekly prompt
     */
    private function buildCoordinatorWeeklyPrompt(string $activitiesText, string $learningsText, string $assessment): array
    {
        $system = "You are an academic writing expert. Create a polished, professional weekly summary for an internship program report.";
        
        $user = "STUDENT ACTIVITIES: {$activitiesText}

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

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ];
    }

    /**
     * Build default weekly prompt
     */
    private function buildDefaultWeeklyPrompt(string $activitiesText, string $learningsText, string $assessment): array
    {
        return $this->buildChairWeeklyPrompt($activitiesText, $learningsText, $assessment);
    }
}

