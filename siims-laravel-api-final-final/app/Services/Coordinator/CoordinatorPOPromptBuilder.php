<?php

namespace App\Services\Coordinator;

/**
 * Coordinator PO Prompt Builder
 * 
 * Builds comprehensive prompts for PO (Program Outcome) analysis for coordinator views.
 * Analyzes a single student's activities and learnings to identify achieved POs.
 */
class CoordinatorPOPromptBuilder
{
    /**
     * PO detailed descriptions
     */
    private const PO_DETAILED = [
        'PO1' => [
            'description' => 'Apply knowledge of computing, science, and mathematics in solving computing/IT-related problems through critical and creative thinking.',
            'examples' => 'Using formulas, calculations, algorithms, data structures, programming logic, solving technical problems, applying theoretical concepts, working with databases, processing data, using mathematical operations, critical thinking, creative problem-solving.',
            'context_indicators' => 'Keywords: apply, compute, calculate, solve, use knowledge. Any activity involving problem-solving, coding, data manipulation, calculations, logical reasoning, applying programming concepts, working with software, handling technical challenges, critical thinking, creative solutions.'
        ],
        'PO2' => [
            'description' => 'Use current best practices and standards in solving complex computing/IT-related problems and requirements.',
            'examples' => 'Following industry standards, best practices, quality guidelines, performance optimization, meeting requirements, using established methods, adhering to protocols, following procedures, implementing quality standards.',
            'context_indicators' => 'Keywords: standard, best practice, quality, performance, requirement. Any mention of following standards, best practices, quality guidelines, performance optimization, meeting requirements, using established methods, protocols, procedures.'
        ],
        'PO3' => [
            'description' => 'Analyze complex computing/IT-related problems by applying analytical and quantitative reasoning, and define the computing requirements appropriate to its solution.',
            'examples' => 'Troubleshooting, debugging, analyzing errors, identifying root causes, evaluating alternatives, making decisions, reviewing code, testing solutions, investigating issues, problem diagnosis, quantitative analysis, requirement definition.',
            'context_indicators' => 'Keywords: analyze, troubleshoot, test, debug, identify, evaluate. Any mention of fixing bugs, solving problems, investigating issues, analyzing situations, making decisions, evaluating options, reviewing work, testing approaches, quantitative reasoning.'
        ],
        'PO4' => [
            'description' => 'Identify and analyze user needs and take them into account in the selection, creation, evaluation, and administration of computer-based systems.',
            'examples' => 'Understanding requirements, gathering needs, considering user feedback, analyzing requirements, meeting user expectations, addressing client needs, user-focused work, requirement analysis, user evaluation, stakeholder consultation.',
            'context_indicators' => 'Keywords: user need, requirement analysis, evaluation, feedback. Any mention of understanding requirements, user needs, client requirements, stakeholder needs, user feedback, addressing needs, requirement gathering, meeting expectations, user evaluation.'
        ],
        'PO5' => [
            'description' => 'Design creatively, implement, and evaluate different computer-based systems, processes, components, or programs to meet desired needs and requirements under various constraints.',
            'examples' => 'Planning features, creating systems, building applications, developing modules, setting up configurations, implementing solutions, creating designs, building components, designing solutions, evaluating systems, working within constraints.',
            'context_indicators' => 'Keywords: design, develop, implement, create, build, deploy. Any activity involving creating, building, developing, implementing, designing, setting up, configuring, constructing systems or software components, creative design, evaluation.'
        ],
        'PO6' => [
            'description' => 'Integrate effectively the IT-based solutions into the user environment with appropriate consideration for public health and safety, cultural, societal, and environmental concerns.',
            'examples' => 'Integrating systems, adapting solutions, maintaining environments, considering safety, sustainability, cultural aspects, societal impact, environmental concerns, public health considerations, system integration.',
            'context_indicators' => 'Keywords: integrate, adapt, maintain, environment, safety, sustainability. Any mention of integrating systems, adapting solutions, maintaining environments, considering safety, sustainability, cultural, societal, environmental, or public health concerns.'
        ],
        'PO7' => [
            'description' => 'Select, adapt, and apply appropriate techniques, resources, skills, and modern computing tools to complex computing activities, with an understanding of the limitations.',
            'examples' => 'Using programming languages, frameworks, libraries, APIs, development tools, software platforms, technology stacks, modern methodologies, industry tools, selecting tools, adapting techniques, understanding limitations.',
            'context_indicators' => 'Keywords: tool, modern technology, programming, configure, software. Using any programming language, framework, tool, software, platform, library, API, or technology. Learning new tools, working with modern technologies, selecting appropriate tools, adapting techniques.'
        ],
        'PO8' => [
            'description' => 'Function effectively as an individual, or work collaboratively and respectfully as a member or leader in diverse development teams and in multidisciplinary and/or multicultural settings.',
            'examples' => 'Working with others, collaborating, team meetings, group projects, coordinating tasks, leading discussions, assisting colleagues, working together, sharing responsibilities, diverse teams, multidisciplinary work, multicultural settings.',
            'context_indicators' => 'Keywords: team, collaborate, assist, coordinate, leader. Any mention of working with team members, colleagues, supervisors, meetings, collaboration, group work, coordinating, helping others, being part of a team, leading, diverse teams, multidisciplinary, multicultural.'
        ],
        'PO9' => [
            'description' => 'Assist in the creation of an effective IT project plan.',
            'examples' => 'Creating project plans, planning timelines, scheduling tasks, documenting plans, project documentation, planning activities, creating schedules, project management, timeline creation.',
            'context_indicators' => 'Keywords: plan, project plan, timeline, documentation, scheduling. Any mention of creating plans, project planning, timelines, scheduling, documentation, project management, planning activities, creating schedules.'
        ],
        'PO10' => [
            'description' => 'Communicate effectively in both oral and written form by being able to deliver and comprehend instructions clearly; and present persuasively to diverse audiences the complex computing/IT-related ideas and perspectives.',
            'examples' => 'Presentations, reports, emails, meetings, discussions, documentation, explaining concepts, writing documentation, verbal communication, written reports, presenting work, delivering instructions, comprehending requirements, persuasive presentations.',
            'context_indicators' => 'Keywords: communicate, present, report, explain, document. Any form of communication: writing reports, emails, documentation, giving presentations, attending meetings, explaining work, discussing with others, verbal or written communication, delivering instructions, presenting to audiences.'
        ],
        'PO11' => [
            'description' => 'Assess local and global impact of computing and information technology on individuals, organizations, and society.',
            'examples' => 'Understanding user impact, considering business effects, thinking about organizational benefits, recognizing social implications, understanding market needs, considering client impact, assessing effects, global perspectives, local impact, societal impact.',
            'context_indicators' => 'Keywords: impact, society, organization, community, global. Any mention of how work affects users, clients, organization, business, customers, stakeholders, local or global impact, societal effects, or any consideration of broader impact or benefits.'
        ],
        'PO12' => [
            'description' => 'Act in recognition of professional, ethical, legal, security, and social responsibilities in the utilization of information technology.',
            'examples' => 'Following company policies, respecting confidentiality, handling data properly, ethical considerations, professional behavior, following procedures, adhering to standards, responsible practices, legal compliance, security practices, privacy protection.',
            'context_indicators' => 'Keywords: ethics, privacy, law, responsibility, security, professionalism. Following policies, procedures, standards, ethical practices, professional conduct, handling sensitive information, respecting privacy, responsible behavior, legal compliance, security awareness.'
        ],
        'PO13' => [
            'description' => 'Recognize the need to engage in independent learning and stay updated with the latest developments in specialized IT fields such as Database Management and Information Systems, Network Design and Administration, and Computer Vision and Image Processing for continual professional development.',
            'examples' => 'Learning new things, self-study, researching, exploring new technologies, improving skills, seeking knowledge, studying resources, gaining expertise, professional growth, independent learning, staying updated, specialized fields, continual development.',
            'context_indicators' => 'Keywords: learn independently, explore, research, self-study, improve skills. Learning, studying, researching, exploring, improving skills, seeking knowledge, gaining experience, professional development, acquiring new knowledge, independent learning, staying updated with latest developments.'
        ],
        'PO14' => [
            'description' => 'Participate in the generation of new knowledge or in research and development projects aligned with local and national development agendas or goals, contributing to the local and national economy.',
            'examples' => 'Research activities, innovation work, development projects, contributing to projects, experimentation, investigation, generating new knowledge, research participation, local development, national goals, economic contribution.',
            'context_indicators' => 'Keywords: research, innovation, development, contribution, national goal. Contributing to development work, research activities, innovation, experimentation, investigation, generating knowledge, participating in R&D, local and national development, economic contribution.'
        ],
        'PO15' => [
            'description' => 'Preserve and promote Filipino historical and cultural heritage.',
            'examples' => 'Using Filipino language, understanding local context, considering cultural aspects, working with local clients, understanding Filipino market, cultural awareness, local context, preserving heritage, promoting culture, Filipino values, historical awareness.',
            'context_indicators' => 'Keywords: Filipino, culture, heritage, values. Working with Filipino clients, understanding local context, using Filipino language, considering cultural aspects, local market understanding, cultural awareness, preserving heritage, promoting Filipino culture and values, historical awareness.'
        ]
    ];

    /**
     * Build PO analysis prompt for coordinator (single student)
     *
     * @param string $text
     * @param int|null $week
     * @param array $activities
     * @param array $learnings
     * @return array System and user messages
     */
    public function buildPOAnalysisPrompt(string $text, ?int $week, array $activities, array $learnings): array
    {
        $weekLabel = $week ? (string)$week : 'the selected week';
        
        // Build PO context guide
        $poContextGuide = "";
        foreach (self::PO_DETAILED as $code => $info) {
            $poContextGuide .= "\n{$code}: {$info['description']}\n";
            $poContextGuide .= "Practical Examples: {$info['examples']}\n";
            $poContextGuide .= "What to Look For: {$info['context_indicators']}\n";
        }
        
        // Build system message
        $system = $this->buildSystemMessage($poContextGuide, $weekLabel);
        
        // Build user message
        $user = $this->buildUserMessage($text, $activities, $learnings);
        
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user]
        ];
    }

    /**
     * Build system message for coordinator (single student context)
     */
    private function buildSystemMessage(string $poContextGuide, string $weekLabel): string
    {
        return "You are a BSIT internship evaluator. Your PRIMARY JOB is to identify Program Outcomes (POs) from raw weekly reports for a SINGLE STUDENT (coordinator view).

PROGRAM OUTCOMES (PO1-PO15):
{$poContextGuide}

MANDATORY PO IDENTIFICATION PROCESS (YOU MUST DO THIS - NO EXCEPTIONS):
Step 1: Read EVERY SINGLE activity/task and learning from RAW WEEKLY REPORT DATA section below
Step 2: For EACH activity/learning, identify ALL POs it demonstrates using the mapping guide (one activity can show multiple POs)
Step 3: Go through ALL 15 POs one by one and check if ANY activity/learning shows evidence
Step 4: If you find ANY evidence, add it to pos_hit array with po and reason - BE LENIENT and GENEROUS, not strict
Step 5: Only add to pos_not_hit if you find ABSOLUTELY NO evidence after checking everything
Step 6: BUILD pos_hit FIRST - it should contain objects like {\"po\": \"PO5\", \"reason\": \"...\"} for ALL achieved POs
Step 7: Then build po_context_hit and po_word_hit arrays from the pos_hit you found
Step 8: Ensure pos_hit contains ALL POs from both po_context_hit AND po_word_hit combined
Step 9: DO NOT limit the number of POs - if 10 or 15 POs are achieved, include ALL of them in pos_hit

PO ANALYSIS SOURCE (CRITICAL):
- Analyze POs using ONLY \"RAW WEEKLY REPORT DATA\" (Activities/Tasks + Learnings)
- IGNORE summary text for PO analysis - it changes and is unreliable
- Activities/Learnings = SOURCE OF TRUTH for PO matching
- This is for a SINGLE STUDENT - individual student analysis

SUMMARY GENERATION:
- Summary can vary based on combined text
- Summary changes do NOT affect PO results

YOUR TASKS (IN ORDER):
1. Read RAW WEEKLY REPORT DATA (Activities/Tasks and Learnings) - THIS IS YOUR SOURCE
2. Refine activities/learnings (fix grammar only, preserve meaning)
3. Generate summary in THIRD-PERSON (the student, they, their - NO I, me, we)
4. MANDATORY PO ANALYSIS: 
   a) Go through EACH activity/learning
   b) For EACH activity/learning, identify which POs it demonstrates
   c) Build pos_hit array with objects: [{\"po\": \"PO5\", \"reason\": \"The student participated in orientation demonstrating teamwork\"}]
   d) Build pos_not_hit array with ALL POs that have NO evidence - MUST include ALL 15 POs that are NOT in pos_hit
   e) Build po_word_hit array with PO codes where keywords were found
   f) Build po_context_hit array with PO codes where context indicates achievement
   g) Ensure pos_hit contains ALL POs from po_word_hit AND po_context_hit
5. Recommendations: CRITICAL - Generate ONE recommendation for EACH PO in pos_not_hit array
   STEP-BY-STEP PROCESS FOR RECOMMENDATIONS:
   a) Count how many POs are in your pos_not_hit array (e.g., if pos_not_hit has 9 POs, you need 9 recommendations)
   b) For EACH PO in pos_not_hit, create ONE specific recommendation
   c) Go through pos_not_hit one by one: PO6 → write recommendation for PO6, PO7 → write recommendation for PO7, etc.
   d) DO NOT skip any PO - every PO in pos_not_hit MUST have exactly one recommendation
   e) The final recommendations array MUST have the EXACT SAME COUNT as pos_not_hit array
   
   REQUIREMENTS:
   - You MUST generate a recommendation for EVERY PO that is in pos_not_hit
   - If pos_not_hit has 9 POs, you MUST generate EXACTLY 9 recommendations (one per PO, no more, no less)
   - DO NOT use ranges (e.g., \"PO6-PO9\" or \"PO6, PO7, PO8\") - each PO must have its OWN separate recommendation
   - DO NOT combine multiple POs in one recommendation - each recommendation addresses ONE specific PO
   - Write in THIRD-PERSON (the student, they, their) - sound like a real educator's suggestions
   - Make them SPECIFIC and CONCRETE, not vague or generic
   - Base recommendations on actual activities/learnings observed in the data
   - Use natural, conversational language - avoid robotic or template-like phrasing
   - Each recommendation should be a complete, natural sentence
   - Focus on actionable steps the student can take to achieve that specific PO
   - Format: Each recommendation should mention the PO code (e.g., \"The student should... to achieve PO6\" or \"...which will help develop PO7 skills\")
   - CRITICAL: Count your recommendations before returning - it MUST match pos_not_hit count exactly!
   - CRITICAL: If pos_not_hit = [PO6, PO7, PO8, PO9], then recommendations MUST be 4 separate items, one for PO6, one for PO7, one for PO8, one for PO9

PO RECOGNITION RULES (APPLY THESE):
- \"Orientation\" or \"attended\" = PO8 (team/collaboration), PO10 (communication), PO12 (professional/ethical), PO13 (learning)
- \"Discussed\" or \"talked\" = PO10 (communication), PO8/PO9 (teamwork/project planning)
- \"Learned about\" or \"understood\" = PO4 (user needs), PO13 (independent learning)
- \"Used\" or \"worked with\" any tool/system = PO7 (tools/techniques)
- \"Fixed\" or \"solved\" = PO1 (knowledge), PO3 (analysis)
- \"Created\" or \"built\" = PO5 (design/implement), PO6 (integrate), PO14 (research/contribution)
- \"Tested\" or \"checked\" = PO3 (analysis/evaluation), PO12 (testing/quality)
- \"Followed\" or \"adhered to\" = PO2 (standards), PO12 (professional/ethical)
- \"Planned\" or \"scheduled\" = PO9 (project planning)
- \"Researched\" or \"studied\" = PO13 (learning), PO14 (research/development)
- ANY work done = Multiple POs achieved (typically 3-8+ POs)

CONTEXTUAL INTERPRETATION METHODOLOGY:
CRITICAL: Use ONLY \"RAW WEEKLY REPORT DATA\" for PO analysis. Ignore summary text completely.

ENFORCED PROCESS:
1. Read EVERY activity/task and learning - do not skip any
2. For EACH activity/learning, ask: \"Which POs does this demonstrate?\" (can be multiple POs)
3. For EACH of 15 POs, ask: \"Do ANY activities/learnings show this PO?\"
4. Be GENEROUS in interpretation - participated = teamwork (PO5/PO9), discussed = communication (PO6)
5. Mark PO as achieved if there is ANY reasonable connection to activities/learnings
6. Build pos_hit aggressively - it is better to include a PO than miss it - NO LIMITS on how many POs can be achieved
7. DO NOT impose any maximum limit - if all 15 POs are achieved, include all 15 in pos_hit
8. Generate HUMANIZED, SPECIFIC recommendations with concrete actions - write like a real educator, not a robot

COMMON ACTIVITY TO PO MAPPINGS:
- Any meeting/orientation maps to PO8 (team), PO10 (communication), PO12 (professional), PO13 (learning)
- Any discussion/talk maps to PO8 (team), PO10 (communication)
- Any learning/understanding maps to PO4 (user needs), PO13 (learning)
- Any tool/software use maps to PO7 (tools/techniques)
- Any problem solved maps to PO1 (knowledge), PO3 (analysis)
- Any creation/development maps to PO5 (design/implement), PO6 (integrate), PO14 (research)
- Any testing maps to PO3 (analysis), PO12 (testing/quality)
- Any following rules/standards maps to PO2 (standards), PO12 (professional/ethical)
- Any planning/scheduling maps to PO9 (project planning)
- Any research/study maps to PO13 (learning), PO14 (research/development)

CRITICAL ENFORCEMENT:
- You MUST identify ALL POs that are demonstrated in the activities/learnings - NO LIMITS
- Empty pos_hit is ONLY acceptable if activities/learnings section is completely empty
- Be PROACTIVE and GENEROUS in finding POs - if there's ANY connection, mark it
- Check activities/learnings multiple times - you might miss POs on first pass
- DO NOT limit the number of POs - identify ALL possible POs that are achieved
- There is NO maximum or minimum number of POs - identify ALL that apply
- If you see \"orientation\", \"discussed\", \"learned\" - these show PO5, PO6, PO13 (and potentially more)
- A single activity can demonstrate multiple POs - identify ALL of them

JSON RESPONSE REQUIREMENTS:
- Return ONLY valid JSON - no explanations, no text before or after
- Start with { and end with }
- pos_hit MUST be an array of objects: [{\"po\": \"PO5\", \"reason\": \"...\"}, ...]
- Do NOT return empty pos_hit array if activities/learnings exist
- NOTE: Summary generation is handled separately by OpenAI summarization - DO NOT generate summaries
- ALL keys must be present: corrected_activities, corrected_learnings, pos_hit, pos_not_hit, po_word_hit, po_context_hit, recommendations
- CRITICAL: The recommendations array MUST contain exactly ONE recommendation for EACH PO in pos_not_hit (same count)

OUTPUT FORMAT (MANDATORY - RETURN THIS EXACT JSON STRUCTURE):
{
  \"corrected_activities\": [\"activity 1\", \"activity 2\"],
  \"corrected_learnings\": [\"learning 1\", \"learning 2\"],
  \"pos_hit\": [
    {\"po\": \"PO5\", \"reason\": \"The student participated in orientation activities demonstrating teamwork\"},
    {\"po\": \"PO6\", \"reason\": \"The student engaged in discussions showing communication skills\"}
  ],
  \"pos_not_hit\": [
    {\"po\": \"PO1\", \"reason\": \"No evidence of mathematical or computational knowledge application\"},
    {\"po\": \"PO6\", \"reason\": \"No evidence of integrating IT solutions\"},
    {\"po\": \"PO7\", \"reason\": \"No evidence of using modern computing tools\"}
  ],
  \"po_word_hit\": [\"PO6\"],
  \"po_context_hit\": [\"PO5\", \"PO6\", \"PO10\", \"PO13\"],
  \"recommendations\": [
    \"The student should engage in activities that require mathematical calculations and algorithm design to develop PO1 skills.\",
    \"The student should work on integration projects and system configuration to develop PO6 competencies.\",
    \"The student should explore and utilize various development tools and modern technologies to enhance PO7 technical skills.\"
  ]
  NOTE: Notice that pos_not_hit has 3 POs (PO1, PO6, PO7) and recommendations has 3 items - ONE per PO!
}

CRITICAL: The pos_hit array MUST contain objects with \"po\" and \"reason\" keys. Do NOT return empty arrays if activities/learnings exist.

FINAL VALIDATION CHECKLIST (DO THIS BEFORE RETURNING JSON):
1. Did I read ALL activities/tasks from RAW WEEKLY REPORT DATA?
2. Did I read ALL learnings from RAW WEEKLY REPORT DATA?
3. Did I check EACH of the 15 POs against the activities/learnings?
4. Did I build pos_hit array with objects containing \"po\" and \"reason\" keys?
5. Is pos_hit array populated with at least some POs if activities/learnings exist?
6. Did I combine all found POs into po_context_hit and po_word_hit arrays?
7. Does pos_hit contain ALL POs from po_context_hit AND po_word_hit?
8. CRITICAL: Did I build pos_not_hit array with ALL remaining POs (PO1-PO15) that are NOT in pos_hit? Every PO must be in either pos_hit OR pos_not_hit - no PO should be missing!
9. CRITICAL: Did I generate EXACTLY ONE recommendation for EACH PO in pos_not_hit? 
   - Count the POs in pos_not_hit array
   - Count the items in recommendations array
   - These two counts MUST BE EQUAL!
   - If pos_not_hit has 9 POs, recommendations MUST have 9 items
   - Go through each PO in pos_not_hit and verify it has a corresponding recommendation
   - Did I avoid using ranges? Each PO must have its own separate recommendation
   - Did I avoid combining multiple POs in one recommendation?
   - DO NOT return until the counts match and every PO has its own recommendation!

Return ONLY the JSON object, no additional text before or after.";
    }

    /**
     * Build user message for coordinator (single student)
     */
    private function buildUserMessage(string $text, array $activities, array $learnings): string
    {
        // Prepare activities and learnings text
        if (empty($activities) && empty($learnings)) {
            $activitiesText = $text;
            $learningsText = '';
        } else {
            $activitiesText = !empty($activities) ? implode("\n", array_map(function($a, $i) {
                return ($i + 1) . ". " . $a;
            }, $activities, array_keys($activities))) : 'No specific activities documented.';
            
            $learningsText = !empty($learnings) ? implode("\n", array_map(function($l, $i) {
                return ($i + 1) . ". " . $l;
            }, $learnings, array_keys($learnings))) : 'No specific learnings documented.';
        }
        
        $summaryText = $text;
        $poAnalysisText = "=== RAW WEEKLY REPORT DATA (SOURCE OF TRUTH FOR PO ANALYSIS) ===\n\n" .
                         "STUDENT ACTIVITIES/TASKS (from database - SINGLE STUDENT):\n" . $activitiesText . "\n\n" .
                         "STUDENT LEARNINGS (from database - SINGLE STUDENT):\n" . $learningsText;
        
        return "=== IGNORE THIS FOR PO ANALYSIS ===\n" .
               "SUMMARY GENERATION DATA (for summary only, can vary):\n" . 
               substr($summaryText, 0, 500) . "...\n\n" .
               "=========================================\n\n" .
               "=== USE THIS FOR PO ANALYSIS (RAW DATA FROM DATABASE - MANDATORY) ===\n" .
               $poAnalysisText . "\n\n" .
               "CRITICAL INSTRUCTIONS:\n" .
               "1. Read the STUDENT ACTIVITIES/TASKS and STUDENT LEARNINGS sections above (from SINGLE STUDENT)\n" .
               "2. For each activity/learning, identify which POs it demonstrates\n" .
               "3. Build pos_hit array with objects: [{\"po\": \"PO5\", \"reason\": \"The student participated in orientation showing teamwork\"}, ...]\n" .
               "4. If you see words like 'participated', 'orientation', 'discussed', 'learned', 'house rules', 'projects' - these DEMONSTRATE MULTIPLE POs\n" .
               "5. pos_hit MUST NOT be empty if activities/learnings exist\n" .
               "6. Build pos_not_hit with ALL POs (PO1-PO15) that are NOT in pos_hit\n" .
               "7. RECOMMENDATIONS - CRITICAL: After building pos_not_hit, you MUST:\n" .
               "   a) Count how many POs are in pos_not_hit (e.g., 9 POs)\n" .
               "   b) Create EXACTLY that many recommendations (e.g., 9 recommendations)\n" .
               "   c) For EACH PO in pos_not_hit, write ONE recommendation\n" .
               "   d) Example: If pos_not_hit = [PO6, PO7, PO9], then recommendations = [rec for PO6, rec for PO7, rec for PO9]\n" .
               "   e) DO NOT return until recommendations count = pos_not_hit count!\n" .
               "8. Return valid JSON with pos_hit populated based on the RAW DATA above\n" .
               "Do NOT use the summary text for PO analysis.";
    }
}

