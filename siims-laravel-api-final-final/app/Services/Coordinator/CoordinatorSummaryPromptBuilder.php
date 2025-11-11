<?php

namespace App\Services\Coordinator;

/**
 * Coordinator Summary Prompt Builder
 * 
 * Builds prompts for coordinator summary generation (single student).
 * Moved from App\Services\OpenAI\CoordinatorPromptBuilder
 */
class CoordinatorSummaryPromptBuilder
{
    /**
     * PO word mapping for keyword detection
     */
    private const PO_WORD_MAP = [
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

    /**
     * PO definitions for contextual analysis
     */
    private const PO_DEFINITIONS = [
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

    /**
     * Build prompt for coordinator summary
     *
     * @param array $activities
     * @param array $learnings
     * @param string $assessment
     * @return string
     */
    public function buildPrompt(array $activities, array $learnings, string $assessment): string
    {
        $text = trim(implode(' ', $learnings) . ' ' . implode(' ', $activities));
        $lower = mb_strtolower($text);
        
        // Detect PO word hits
        $hits = $this->detectPOWordHits($lower);
        
        $intro = "You are an academic writing expert. Write a polished, professional weekly summary for a single student's internship journal. Always write in third person and do not use first-person words.";
        $reqs = "Begin EXACTLY with: 'For this week, the student '. Produce 2–3 coherent sentences that combine ACTIVITIES and LEARNINGS. Do NOT list or mention PO codes in the summary. Ensure excellent grammar and flow.";
        $source = "SOURCE TEXT (cleaned): \n". $text ."\n";
        
        if (!empty($hits)) {
            $hitLines = [];
            foreach ($hits as $po => $info) {
                $hitLines[] = $po . ' => words: ' . implode(', ', $info['matched']);
            }
            $hitSection = "PO WORD HITS (synonym match):\n" . implode("\n", $hitLines) . "\n";
            return $intro."\n".$reqs."\n".$source.$hitSection.
                "TASK: Use the PO WORD HITS only to understand the context, but DO NOT mention PO codes in the summary. Return only the summary paragraph.";
        } else {
            $defs = [];
            foreach (self::PO_DEFINITIONS as $po => $def) { 
                $defs[] = $po.': '.$def; 
            }
            $defSection = "CONTEXTUAL PROGRAM OUTCOME DEFINITIONS:\n" . implode("\n", $defs) . "\n";
            return $intro."\n".$reqs."\n".$source.$defSection.
                "TASK: No direct PO words matched. Use the definitions only for context; DO NOT mention PO codes. Return only the summary paragraph.";
        }
    }

    /**
     * Detect PO word hits in text
     *
     * @param string $lowerText
     * @return array
     */
    private function detectPOWordHits(string $lowerText): array
    {
        $hits = [];
        foreach (self::PO_WORD_MAP as $po => $words) {
            $count = 0;
            $matched = [];
            foreach ($words as $w) {
                $needle = mb_strtolower($w);
                if (str_contains($lowerText, $needle)) {
                    $count++;
                    $matched[] = $w;
                }
            }
            if ($count > 0) {
                $hits[$po] = ['count' => $count, 'matched' => $matched];
            }
        }
        return $hits;
    }
}

