<?php

namespace App\Services\Chairperson;

/**
 * Chairperson PO Prompt Builder
 * 
 * Builds comprehensive prompts for PO (Program Outcome) analysis for chairperson views.
 * Analyzes multiple students' activities and learnings to identify achieved POs.
 */
class ChairpersonPOPromptBuilder
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
     * Build PO analysis prompt for chairperson (multiple students)
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
     * Build system message for chairperson (multiple students context)
     */
    private function buildSystemMessage(string $poContextGuide, string $weekLabel): string
    {
        return "You are a BSIT internship evaluator. Identify Program Outcomes (POs) from weekly reports for MULTIPLE STUDENTS.

PROGRAM OUTCOMES (PO1-PO15):
{$poContextGuide}

PO IDENTIFICATION PROCESS:
1. Read ALL activities/learnings from JSON data below
2. For each activity/learning, identify ALL POs it demonstrates (one activity can show multiple POs)
3. Check all 15 POs - if ANY evidence found, add to pos_hit: [{\"po\": \"PO5\", \"reason\": \"...\"}]
4. Build pos_not_hit with ALL POs (PO1-PO15) NOT in pos_hit
5. Build po_context_hit and po_word_hit from pos_hit
6. Generate EXACTLY ONE recommendation per PO in pos_not_hit (same count)

CRITICAL RULES:
- Use ONLY JSON data (activities/learnings) for PO analysis - ignore summary text
- Be GENEROUS - one activity can demonstrate multiple POs
- pos_hit must contain objects with \"po\" and \"reason\" keys
- Empty pos_hit only if activities/learnings are empty
- NO limits on number of POs - identify ALL that apply
- recommendations count MUST equal pos_not_hit count (ONE per PO)
- Write recommendations in third-person (students, they, them), specific and actionable

QUICK PO MAPPING:
- Orientation/attended → PO8, PO10, PO12, PO13
- Discussed/talked → PO8, PO10
- Learned/understood → PO4, PO13
- Used tool/system → PO7
- Fixed/solved → PO1, PO3
- Created/built → PO5, PO6, PO14
- Tested/checked → PO3, PO12
- Followed rules → PO2, PO12
- Planned/scheduled → PO9
- Researched/studied → PO13, PO14

JSON RESPONSE FORMAT:
{
  \"corrected_activities\": [\"activity 1\", \"activity 2\"],
  \"corrected_learnings\": [\"learning 1\", \"learning 2\"],
  \"pos_hit\": [{\"po\": \"PO5\", \"reason\": \"Students participated in orientation demonstrating teamwork\"}],
  \"pos_not_hit\": [{\"po\": \"PO1\", \"reason\": \"No evidence of...\"}],
  \"po_word_hit\": [\"PO6\"],
  \"po_context_hit\": [\"PO5\", \"PO10\"],
  \"recommendations\": [\"Students should... to achieve PO1.\"]
}

VALIDATION:
- pos_hit has objects with \"po\" and \"reason\"
- pos_not_hit has ALL POs not in pos_hit (PO1-PO15)
- recommendations count = pos_not_hit count (ONE per PO)
- Return ONLY JSON, no extra text.";
    }

    /**
     * Build user message for chairperson (multiple students)
     * OPTIMIZED: Convert activities and learnings to JSON for faster OpenAI processing
     */
    private function buildUserMessage(string $text, array $activities, array $learnings): string
    {
        // OPTIMIZATION: Convert activities and learnings to structured JSON format
        $dataJson = [
            'activities' => !empty($activities) ? $activities : [],
            'learnings' => !empty($learnings) ? $learnings : [],
            'total_activities' => count($activities),
            'total_learnings' => count($learnings)
        ];
        
        $jsonData = json_encode($dataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $summaryText = substr($text, 0, 500);
        
        return "=== IGNORE FOR PO ANALYSIS ===\n" .
               "SUMMARY DATA: " . $summaryText . "...\n\n" .
               "=== USE FOR PO ANALYSIS (RAW DATA) ===\n" .
               "STUDENT DATA (MULTIPLE STUDENTS) - JSON:\n" .
               $jsonData . "\n\n" .
               "INSTRUCTIONS:\n" .
               "1. Parse JSON - identify POs from activities/learnings\n" .
               "2. Build pos_hit: [{\"po\": \"PO5\", \"reason\": \"...\"}]\n" .
               "3. Build pos_not_hit with ALL remaining POs (PO1-PO15)\n" .
               "4. Generate ONE recommendation per PO in pos_not_hit (same count)\n" .
               "5. Return valid JSON - ignore summary text.";
    }
}

