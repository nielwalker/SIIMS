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
        if (!$raw || trim($raw) === '') {
            return ['hit' => $hit, 'notHit' => $notHit];
        }
        $content = (string)$raw;
        
        // Try to extract JSON from code blocks first
        $content = preg_replace_callback('/```json[\s\S]*?```/i', function ($m) {
            return preg_replace('/```json|```/i', '', $m[0]);
        }, $content) ?? $content;
        
        // Try multiple JSON extraction methods - be aggressive in finding JSON
        $decoded = null;
        
        // Method 1: Try to find JSON object with pos_hit (greedy match, handles nested objects)
        if (preg_match('/\{[\s\S]*?"pos_hit"[\s\S]*?\}/', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[0], true);
            if (!is_array($decoded) || !isset($decoded['pos_hit'])) {
                $decoded = null; // Try next method if this didn't work
            }
        }
        
        // Method 2: Try to find the largest JSON object (might contain pos_hit)
        if (!$decoded && preg_match('/\{[\s\S]{20,}\}/', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[0], true);
        }
        
        // Method 3: Try decoding the entire content (if it's pure JSON)
        if (!$decoded) {
            $decoded = json_decode($content, true);
        }
        
        // Method 4: Try to extract JSON from code blocks (already handled above, but try again)
        if (!$decoded && preg_match('/```[\s\S]*?```/', $content)) {
            $cleaned = preg_replace('/```(?:json)?/i', '', $content);
            $cleaned = preg_replace('/```/', '', $cleaned);
            $decoded = json_decode(trim($cleaned), true);
        }
        
        // Method 5: Try to find JSON object by looking for opening and closing braces more carefully
        if (!$decoded) {
            $start = strpos($content, '{');
            if ($start !== false) {
                $braceCount = 0;
                $end = $start;
                for ($i = $start; $i < strlen($content); $i++) {
                    if ($content[$i] === '{') $braceCount++;
                    if ($content[$i] === '}') {
                        $braceCount--;
                        if ($braceCount === 0) {
                            $end = $i;
                            break;
                        }
                    }
                }
                if ($end > $start) {
                    $jsonStr = substr($content, $start, $end - $start + 1);
                    $decoded = json_decode($jsonStr, true);
                }
            }
        }
        
        // Log for debugging
        \Log::info('Extracting PO arrays', [
            'has_decoded' => is_array($decoded),
            'has_pos_hit' => isset($decoded['pos_hit']),
            'has_po_context_hit' => isset($decoded['po_context_hit']),
            'has_po_word_hit' => isset($decoded['po_word_hit']),
            'content_preview' => substr($content, 0, 200)
        ]);
        
        if (is_array($decoded)) {
            $hit = is_array($decoded['pos_hit'] ?? null) ? $decoded['pos_hit'] : [];
            $notHit = is_array($decoded['pos_not_hit'] ?? null) ? $decoded['pos_not_hit'] : [];
            
            // Normalize pos_hit items to ensure they have 'po' and 'reason' keys
            $hit = array_map(function($item) {
                if (is_string($item)) {
                    return ['po' => $item, 'reason' => 'Evidence found in activities and learnings'];
                }
                if (is_array($item)) {
                    return [
                        'po' => $item['po'] ?? $item[0] ?? '',
                        'reason' => $item['reason'] ?? $item[1] ?? 'Evidence found in activities and learnings'
                    ];
                }
                return null;
            }, $hit);
            $hit = array_filter($hit, function($item) {
                return is_array($item) && !empty($item['po']);
            });
            $hit = array_values($hit);
            
            // If pos_hit is empty but we have po_context_hit, use that as fallback
            if (empty($hit) && is_array($decoded['po_context_hit'] ?? null)) {
                $contextHits = $decoded['po_context_hit'];
                foreach ($contextHits as $poCode) {
                    if (is_string($poCode) && preg_match('/^PO\d+$/', $poCode)) {
                        $hit[] = [
                            'po' => $poCode,
                            'reason' => 'Achieved through contextual activities and practical application'
                        ];
                    }
                }
            }
            
            // Also check po_word_hit if pos_hit is still empty
            if (empty($hit) && is_array($decoded['po_word_hit'] ?? null)) {
                $wordHits = $decoded['po_word_hit'];
                foreach ($wordHits as $poCode) {
                    if (is_string($poCode) && preg_match('/^PO\d+$/', $poCode)) {
                        // Check if already added from context_hit
                        $exists = false;
                        foreach ($hit as $existing) {
                            if (($existing['po'] ?? '') === $poCode) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $hit[] = [
                                'po' => $poCode,
                                'reason' => 'Achieved through keyword matching and explicit evidence'
                            ];
                        }
                    }
                }
            }
            
            \Log::info('Extracted PO arrays result', ['hit_count' => count($hit), 'not_hit_count' => count($notHit)]);
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

    public function summarize(string $text, ?int $week, bool $useGPT = false, array $activities = [], array $learnings = []): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        $summary = '';
        $usedGPT = false;
        $rawContent = null;

        $apiKey = env('OPENAI_API_KEY');
        
        // Check if OpenAI is required but not available
        if ($useGPT && !$apiKey) {
            \Log::warning('OpenAI API key not configured');
            return [
                'error' => 'OpenAI is not available right now',
                'openai_unavailable' => true,
                'summary' => '',
                'usedGPT' => false,
                'posHitExplanation' => '',
                'posNotHitExplanation' => '',
                'poWordHit' => [],
                'poContextHit' => [],
                'recommendations' => [],
                'pos_hit' => [],
                'pos_not_hit' => [],
            ];
        }
        
        if ($useGPT && $apiKey && $clean) {
            try {
                $weekLabel = $week ? (string)$week : 'the selected week';
                
                // Comprehensive PO descriptions with detailed explanations and practical examples
                // Based on official Program Outcomes for BSIT
                $poDetailed = [
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
                
                $poContextGuide = "";
                foreach ($poDetailed as $code => $info) {
                    $poContextGuide .= "\n{$code}: {$info['description']}\n";
                    $poContextGuide .= "Practical Examples: {$info['examples']}\n";
                    $poContextGuide .= "What to Look For: {$info['context_indicators']}\n";
                }
                
                $sys = "You are a BSIT internship evaluator. Your PRIMARY JOB is to identify Program Outcomes (POs) from raw weekly reports.

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

YOUR TASKS (IN ORDER):
1. Read RAW WEEKLY REPORT DATA (Activities/Tasks and Learnings) - THIS IS YOUR SOURCE
2. Refine activities/learnings (fix grammar only, preserve meaning)
3. Generate summary in THIRD-PERSON (students, they, them - NO I, me, we)
4. MANDATORY PO ANALYSIS: 
   a) Go through EACH activity/learning
   b) For EACH activity/learning, identify which POs it demonstrates
   c) Build pos_hit array with objects: [{\"po\": \"PO5\", \"reason\": \"Students participated in orientation demonstrating teamwork\"}]
   d) Build pos_not_hit array with ALL POs that have NO evidence - MUST include ALL 15 POs that are NOT in pos_hit
   e) Build po_word_hit array with PO codes where keywords were found
   f) Build po_context_hit array with PO codes where context indicates achievement
   g) Ensure pos_hit contains ALL POs from po_word_hit AND po_context_hit
5. Recommendations: Generate 3-5 REALISTIC, HUMANIZED, ACTIONABLE recommendations based on pos_not_hit
   - Write in THIRD-PERSON (students, they, them) - sound like a real educator's suggestions
   - Make them SPECIFIC and CONCRETE, not vague or generic
   - Base recommendations on actual activities/learnings observed in the data
   - Use natural, conversational language - avoid robotic or template-like phrasing
   - Each recommendation should be a complete, natural sentence
   - Focus on actionable steps students can take
   - Examples of GOOD recommendations:
     * \"Students should engage in collaborative programming projects to develop teamwork skills and achieve PO5.\"
     * \"Encourage students to participate in code review sessions where they analyze and discuss software solutions, which will help develop PO2 analytical skills.\"
     * \"Students would benefit from hands-on projects that require designing and implementing software solutions to strengthen PO3 and PO4 achievement.\"
   - Avoid vague recommendations like \"improve technical skills\" or \"work on PO3\"
   - Make recommendations sound like thoughtful, practical suggestions from an experienced educator

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
- ALL keys must be present: corrected_activities, corrected_learnings, summary for this section on a week, pos_hit, pos_not_hit, po_word_hit, po_context_hit, recommendations

OUTPUT FORMAT (MANDATORY - RETURN THIS EXACT JSON STRUCTURE):
You MUST return valid JSON with ALL these keys. Do NOT skip pos_hit even if you think no POs are achieved - analyze the activities/learnings and find at least some POs.

{
  \"corrected_activities\": [\"activity 1\", \"activity 2\"],
  \"corrected_learnings\": [\"learning 1\", \"learning 2\"],
  \"summary for this section on a week\": \"In week {$weekLabel}, the students...\",
  \"pos_hit\": [
    {\"po\": \"PO5\", \"reason\": \"Students participated in orientation activities demonstrating teamwork\"},
    {\"po\": \"PO6\", \"reason\": \"Students engaged in discussions showing communication skills\"}
  ],
  \"pos_not_hit\": [
    {\"po\": \"PO1\", \"reason\": \"No evidence of mathematical or computational knowledge application\"}
  ],
  \"po_word_hit\": [\"PO6\"],
  \"po_context_hit\": [\"PO5\", \"PO6\", \"PO10\", \"PO13\"],
  \"recommendations\": [
    \"Students should engage in collaborative programming projects to develop teamwork skills and achieve PO5.\",
    \"Encourage students to participate in code review sessions where they analyze and discuss software solutions, which will help develop PO2 analytical skills.\",
    \"Students would benefit from hands-on projects that require designing and implementing software solutions to strengthen PO3 and PO4 achievement.\"
  ]
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

CRITICAL FINAL REMINDER:
Your response MUST be valid JSON starting with { and ending with }.
The pos_hit array MUST be an array of objects: [{\"po\": \"PO5\", \"reason\": \"Students participated in orientation demonstrating teamwork\"}, ...]
If you see activities/learnings about orientation, discussions, learning, house rules, or any work done, pos_hit MUST NOT be empty.
DO NOT return empty pos_hit. Analyze the RAW WEEKLY REPORT DATA and identify the POs that are demonstrated.

RECOMMENDATIONS REQUIREMENTS (CRITICAL FOR HUMANIZED OUTPUT):
- Generate 3-5 recommendations based on pos_not_hit (POs that were NOT achieved)
- Write in THIRD-PERSON: \"Students should...\", \"They need to...\", \"Encourage students to...\", \"It would be beneficial for students to...\"
- Make each recommendation SPECIFIC, ACTIONABLE, and NATURAL-SOUNDING
- Use natural, human-like language - avoid robotic, template-like, or generic phrasing
- Base recommendations on actual activities/learnings from the data - be contextual
- Each recommendation should be a complete, natural sentence that flows well
- Vary the sentence structure and phrasing - don't repeat the same pattern
- Make recommendations sound like thoughtful, practical suggestions from an experienced educator
- Examples of GOOD (humanized) recommendations:
  * \"Students should engage in collaborative programming projects to develop teamwork skills and achieve PO5.\"
  * \"Encourage students to participate in code review sessions where they analyze and discuss software solutions, which will help develop PO2 analytical skills.\"
  * \"Students would benefit from hands-on projects that require designing and implementing software solutions to strengthen PO3 and PO4 achievement.\"
  * \"It would be valuable for students to work on projects that involve gathering user requirements and understanding stakeholder needs, as this will help them develop PO10 skills.\"
- Examples of BAD (vague/robotic) recommendations to AVOID:
  * \"Improve technical skills\" (too vague)
  * \"Work on PO3\" (not actionable, too brief)
  * \"Students need to develop PO5\" (sounds robotic)
  * \"Enhance PO achievement\" (generic and meaningless)

Return ONLY the JSON object, no additional text before or after.";
                
                // Prepare user message - separate summary generation from PO analysis
                // For PO analysis, use activities and learnings directly (stable, doesn't change)
                // For summary, use combined text (can vary)
                
                // Log what we're sending for PO analysis
                \Log::info('ChairSummaryAdapter: Activities for PO analysis: ' . count($activities));
                \Log::info('ChairSummaryAdapter: Learnings for PO analysis: ' . count($learnings));
                
                if (empty($activities) && empty($learnings)) {
                    // Fallback: if activities/learnings are empty, use the clean text for PO analysis
                    \Log::warning('ChairSummaryAdapter: No activities/learnings extracted, using combined text for PO analysis');
                    $activitiesText = $clean;
                    $learningsText = '';
                } else {
                    $activitiesText = !empty($activities) ? implode("\n", array_map(function($a, $i) {
                        return ($i + 1) . ". " . $a;
                    }, $activities, array_keys($activities))) : 'No specific activities documented.';
                    
                    $learningsText = !empty($learnings) ? implode("\n", array_map(function($l, $i) {
                        return ($i + 1) . ". " . $l;
                    }, $learnings, array_keys($learnings))) : 'No specific learnings documented.';
                }
                
                // For summary generation, use combined text
                $summaryText = $clean;
                
                // For PO analysis, use structured activities and learnings (RAW DATA FROM DATABASE)
                $poAnalysisText = "=== RAW WEEKLY REPORT DATA (SOURCE OF TRUTH FOR PO ANALYSIS) ===\n\n" .
                                 "STUDENT ACTIVITIES/TASKS (from database):\n" . $activitiesText . "\n\n" .
                                 "STUDENT LEARNINGS (from database):\n" . $learningsText;
                
                // Update system prompt to separate PO analysis from summary
                $sys = str_replace(
                    'YOUR TASKS:',
                    'PO ANALYSIS SOURCE (CRITICAL):
- Analyze POs using ONLY "RAW WEEKLY REPORT DATA" (Activities/Tasks + Learnings)
- IGNORE summary text for PO analysis - it changes and is unreliable
- Activities/Learnings = SOURCE OF TRUTH for PO matching

SUMMARY GENERATION:
- Summary can vary based on combined text
- Summary changes do NOT affect PO results

YOUR TASKS:',
                    $sys
                );
                
                // Strengthen the contextual interpretation section
                $sys = str_replace(
                    'CONTEXTUAL INTERPRETATION METHODOLOGY:',
                    'CONTEXTUAL INTERPRETATION METHODOLOGY:
CRITICAL: Use ONLY "RAW WEEKLY REPORT DATA" for PO analysis. Ignore summary text completely.

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

CONTEXTUAL INTERPRETATION METHODOLOGY:',
                    $sys
                );
                
                // Create user message with both sections clearly separated
                // Make PO analysis data MUCH more prominent and clear
                $usr = "=== IGNORE THIS FOR PO ANALYSIS ===\n" .
                       "SUMMARY GENERATION DATA (for summary only, can vary):\n" . 
                       substr($summaryText, 0, 500) . "...\n\n" .
                       "=========================================\n\n" .
                       "=== USE THIS FOR PO ANALYSIS (RAW DATA FROM DATABASE - MANDATORY) ===\n" .
                       $poAnalysisText . "\n\n" .
                       "CRITICAL INSTRUCTIONS:\n" .
                       "1. Read the STUDENT ACTIVITIES/TASKS and STUDENT LEARNINGS sections above\n" .
                       "2. For each activity/learning, identify which POs it demonstrates\n" .
                       "3. Build pos_hit array with objects: [{\"po\": \"PO5\", \"reason\": \"Students participated in orientation showing teamwork\"}, ...]\n" .
                       "4. If you see words like 'participated', 'orientation', 'discussed', 'learned', 'house rules', 'projects' - these DEMONSTRATE MULTIPLE POs\n" .
                       "5. pos_hit MUST NOT be empty if activities/learnings exist\n" .
                       "6. Return valid JSON with pos_hit populated based on the RAW DATA above\n" .
                       "Do NOT use the summary text for PO analysis.";
                
                $resp = Http::withToken($apiKey)->timeout(90)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $sys],
                        ['role' => 'user', 'content' => $usr],
                    ],
                    'temperature' => 0.2, // Very low temperature for maximum consistency and accuracy
                    'max_tokens' => 3000, // Increased for comprehensive PO analysis and recommendations
                    'top_p' => 0.95, // Nucleus sampling for focused responses
                ]);
                
                if ($resp->ok()) {
                    $data = $resp->json();
                    $content = $data['choices'][0]['message']['content'] ?? null;
                    if ($content) { 
                        $rawContent = $content; 
                        $summary = $this->normalizeSummary($content); 
                        $usedGPT = $summary !== '';
                        
                        // Log what OpenAI returned for debugging
                        \Log::info('OpenAI Response Content:', [
                            'length' => strlen($content), 
                            'preview' => substr($content, 0, 1000),
                            'has_pos_hit' => stripos($content, 'pos_hit') !== false,
                            'has_json' => preg_match('/\{[\s\S]*\}/', $content) ? true : false
                        ]);
                    } else {
                        \Log::warning('OpenAI returned empty content in response');
                    }
                } else {
                    \Log::error('OpenAI API request failed:', [
                        'status' => $resp->status(),
                        'body' => $resp->body()
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('OpenAI API Error:', ['message' => $e->getMessage()]);
            }
        }

        // Extract PO analysis from OpenAI response - NO FALLBACK
        if ($usedGPT && !empty($rawContent)) {
            // OpenAI succeeded - extract from response
            $pos = $this->extractPosArrays($rawContent);
            $poTypes = $this->extractPoHitTypes($rawContent);
            $recommendations = $this->extractRecommendations($rawContent);
        } else {
            // OpenAI not available - return error status
            \Log::warning('OpenAI is not available', [
                'openai_used' => $usedGPT,
                'has_raw_content' => !empty($rawContent),
                'api_key_configured' => !empty(env('OPENAI_API_KEY'))
            ]);
            
            return [
                'error' => 'OpenAI is not available right now',
                'openai_unavailable' => true,
                'summary' => '',
                'usedGPT' => false,
                'posHitExplanation' => '',
                'posNotHitExplanation' => '',
                'poWordHit' => [],
                'poContextHit' => [],
                'recommendations' => [],
                'pos_hit' => [],
                'pos_not_hit' => [],
            ];
        }
        
        // CRITICAL: Ensure ALL 15 POs are accounted for
        // If a PO is not in pos_hit, it MUST be in pos_not_hit
        $allPOs = array_map(function($i) {
            return 'PO' . ($i + 1);
        }, range(0, 14));
        
        $hitPOs = array_map(function($item) {
            return is_string($item) ? $item : ($item['po'] ?? '');
        }, $pos['hit']);
        $hitPOs = array_filter($hitPOs, function($po) {
            return !empty($po) && preg_match('/^PO\d+$/', $po);
        });
        
        $notHitPOs = array_map(function($item) {
            return is_string($item) ? $item : ($item['po'] ?? '');
        }, $pos['notHit']);
        $notHitPOs = array_filter($notHitPOs, function($po) {
            return !empty($po) && preg_match('/^PO\d+$/', $po);
        });
        
        // Log what we extracted from OpenAI for debugging
        \Log::info('PO Extraction Results:', [
            'pos_hit_count' => count($pos['hit']),
            'pos_not_hit_count' => count($pos['notHit']),
            'total_accounted' => count($hitPOs) + count($pos['notHit']),
            'po_context_hit' => $poTypes['context'],
            'po_word_hit' => $poTypes['word'],
            'raw_content_length' => strlen($rawContent ?? ''),
            'has_raw_content' => !empty($rawContent),
            'raw_content_preview' => substr($rawContent ?? '', 0, 1000)
        ]);
        
        $posHitExplanation = $this->formatPosExplanation('Explanation on the POs hit', $pos['hit']);
        $posNotHitExplanation = $this->formatPosExplanation('Explanation on the POs not hit', $pos['notHit']);

        $result = [
            'summary' => $summary,
            'usedGPT' => $usedGPT,
            'posHitExplanation' => $posHitExplanation,
            'posNotHitExplanation' => $posNotHitExplanation,
            'poWordHit' => $poTypes['word'],
            'poContextHit' => $poTypes['context'],
            'recommendations' => $recommendations,
            'pos_hit' => $pos['hit'], // Include raw arrays for consistent frontend usage
            'pos_not_hit' => $pos['notHit'],
        ];
        
        // Store activities and learnings separately (used for PO analysis, stable data)
        if (!empty($activities)) {
            $result['corrected_activities'] = $activities;
        }
        if (!empty($learnings)) {
            $result['corrected_learnings'] = $learnings;
        }
        
        return $result;
    }
}