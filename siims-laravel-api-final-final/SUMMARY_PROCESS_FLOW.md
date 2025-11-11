# Summary Process Flow - Coordinator & Chairperson

This document shows the exact syntax and flow of how summary generation and PO analysis works.

---

## 📋 **COORDINATOR FLOW**

### **Step 1: Frontend Request → Controller**
**Route:** `POST /api/v1/summary`  
**Controller:** `CoordinatorSummaryController::generate()`

```php
// File: app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php

public function generate(Request $request, CoordinatorSummaryAdapter $adapter): JsonResponse
{
    // 1. Extract request parameters
    $section = $request->input('section');
    $studentId = $request->input('studentId');
    $coordinatorId = $request->input('coordinatorId');
    $week = $request->integer('week');
    $useGPT = (bool) $request->input('useGPT');
    $analysisType = $request->input('analysisType');
    $isOverall = $request->boolean('isOverall');

    // 2. Query database for weekly entries
    $query = DB::table('weekly_entries as we')
        ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
        ->join('students as s', 's.id', '=', 'we.student_id');

    if ($studentId) {
        $query->where('we.student_id', $studentId);
    }
    if ($coordinatorId) {
        $query->where('s.coordinator_id', $coordinatorId);
    }
    if ($section) {
        $query->where(function ($q) use ($section) {
            $q->where('s.section', $section)
              ->orWhere('s.section_id', $section);
        });
    }

    $rows = $query->when(!$isOverall && $week, function ($q) use ($week) {
            $q->where('we.week_number', $week);
        })
        ->get();

    // 3. Extract activities and learnings (using TextProcessingTrait)
    $extracted = $this->extractActivitiesAndLearnings($rows);
    $activities = $extracted['activities'];
    $learnings = $extracted['learnings'];

    // 4. Build combined text (using TextProcessingTrait)
    $combined = $this->buildCombinedText($rows);

    // 5. Convert to third-person (using TextProcessingTrait)
    $combined = $this->convertToThirdPerson($combined);
```

### **Step 2: Generate Summary → Adapter**
**Next Process:** `CoordinatorSummaryAdapter::analyze()`

```php
    // 6. Call adapter to generate summary + keyword matching
    $summaryResult = $adapter->analyze($combined, $analysisType, $useGPT, $week);
    $summary = $summaryResult['summary'];
    $keywordScores = $summaryResult['keywordScores'] ?? [];
```

**Inside Adapter (`CoordinatorSummaryAdapter::analyze()`):**
```php
// File: app/Services/Coordinator/CoordinatorSummaryAdapter.php

public function analyze(string $text, ?string $analysisType, bool $useGPT = true, ?int $week = null): array
{
    // 1. Clean text using OpenAIService
    $clean = $this->openAIService->cleanText($text);
    
    // 2. Extract week number if not provided
    $weekNumber = $week;
    if ($weekNumber === null && preg_match('/^\[WEEK\s+(\d+)\]\s*/i', $clean, $m)) {
        $weekNumber = (int)($m[1] ?? 0) ?: null;
        $clean = trim(preg_replace('/^\[WEEK\s+\d+\]\s*/i', '', $clean));
    }

    // 3. Perform keyword matching (text mining) for PO identification
    $keywordSets = [
        ['math', 'mathematics', 'science', 'algorithm', 'compute', 'analysis'], // PO1
        ['best practice', 'standard', 'policy', 'method', 'procedure', 'protocol'], // PO2
        // ... (15 PO keyword sets)
    ];
    
    $lower = mb_strtolower($clean);
    $counts = array_map(function ($set) use ($lower) {
        $c = 0;
        foreach ($set as $kw) {
            if (str_contains($lower, $kw)) { $c++; continue; }
            // ... keyword matching logic
        }
        return $c;
    }, $keywordSets);
    
    $total = array_sum($counts) ?: 1;
    $scores = array_map(fn($c) => (int)round(($c / $total) * 100), $counts);

    // 4. Generate summary using OpenAI
    if ($clean && $useGPT && $this->openAIService->isAvailable()) {
        // Build prompt using CoordinatorSummaryPromptBuilder
        $prompt = $this->coordinatorPromptBuilder->buildPrompt($activities, $learnings, '');
        
        // Call OpenAI API
        $response = $this->openAIService->callSimple($prompt, 'gpt-3.5-turbo', 300, 0.6, 30);
        
        if ($response['success']) {
            $summary = $this->openAIService->cleanText($response['summary'] ?? $response['content'] ?? '');
            // Add week prefix if needed
            if (!preg_match('/^For\s+this\s+week,\s+the\s+student/i', $summary)) {
                $weekLabel = $weekNumber ? "week {$weekNumber}" : "this week";
                $summary = "For {$weekLabel}, the student " . ltrim($summary);
            }
            $usedGPT = true;
        }
    }

    return [
        'summary' => $summary,
        'keywordScores' => $scores, // Word-based text mining scores
        'usedGPT' => $usedGPT,
    ];
}
```

### **Step 3: Generate PO Analysis → Service**
**Next Process:** `CoordinatorSummaryService::generateSummaryWithPOAnalysis()`

```php
    // 7. Generate PO analysis using CoordinatorSummaryService
    $poAnalysisResult = [];
    if ($useGPT && !empty($combined) && $studentId) {
        $user = auth()->user();
        if ($user && ($user->hasRole('coordinator') || $user->hasRole('chairperson'))) {
            // Verify access and call service
            $poAnalysisResult = $this->coordinatorSummaryService->generateSummaryWithPOAnalysis(
                $combined, 
                $week, 
                $activities, 
                $learnings
            );
        }
    }
```

**Inside Service (`CoordinatorSummaryService::generateSummaryWithPOAnalysis()`):**
```php
// File: app/Services/Coordinator/CoordinatorSummaryService.php

public function generateSummaryWithPOAnalysis(
    string $text,
    ?int $week = null,
    array $activities = [],
    array $learnings = []
): array {
    // 1. Check OpenAI availability
    if (!$this->openAIService->isAvailable()) {
        return $this->getUnavailableResponse();
    }

    // 2. Clean text
    $clean = $this->openAIService->cleanText($text);
    if (empty($clean)) {
        return $this->getUnavailableResponse();
    }

    // 3. Build PO analysis prompt using CoordinatorPOPromptBuilder
    $prompt = $this->promptBuilder->buildPOAnalysisPrompt($clean, $week, $activities, $learnings);
    
    // 4. Call OpenAI API
    $response = $this->openAIService->call($prompt, [
        'model' => 'gpt-4o-mini',
        'max_tokens' => 3000,
        'temperature' => 0.2,
        'timeout' => 90,
    ]);

    if ($response['success'] && $response['content']) {
        $rawContent = $response['content'];
        
        // 5. Extract PO analysis from OpenAI response
        $pos = $this->extractPosArrays($rawContent);
        $poTypes = $this->extractPoHitTypes($rawContent);
        $recommendations = $this->extractRecommendations($rawContent);
        
        // 6. Ensure all 15 POs are accounted for
        $allPOs = array_map(function($i) {
            return 'PO' . ($i + 1);
        }, range(0, 14));
        
        $hitPOs = array_map(function($item) {
            return is_string($item) ? $item : ($item['po'] ?? '');
        }, $pos['hit']);
        
        $notHitPOs = array_map(function($item) {
            return is_string($item) ? $item : ($item['po'] ?? '');
        }, $pos['notHit']);
        
        // Find missing POs and add to pos_not_hit
        $missingPOs = array_diff($allPOs, array_merge($hitPOs, $notHitPOs));
        foreach ($missingPOs as $po) {
            $pos['notHit'][] = [
                'po' => $po,
                'reason' => $this->getDefaultNotHitReason($po)
            ];
        }
        
        return [
            'summary' => '', // Summary is generated separately
            'usedGPT' => true,
            'posHitExplanation' => $this->formatPosExplanation('Program Outcomes Achieved', $pos['hit']),
            'posNotHitExplanation' => $this->formatPosExplanation('Program Outcomes Not Met', $pos['notHit']),
            'poWordHit' => $poTypes['word'] ?? [],
            'poContextHit' => $poTypes['context'] ?? [],
            'recommendations' => $recommendations,
            'pos_hit' => $pos['hit'],
            'pos_not_hit' => $pos['notHit'],
        ];
    }
}
```

### **Step 4: Evaluation → Evaluation Service**
**Next Process:** `SummaryEvaluationService::evaluate()`

```php
    // 8. Ensure summary formatting
    if (!empty($summary)) {
        $summary = $this->convertToThirdPerson($summary);
        if (!empty($week)) {
            $summary = $this->openAIService->enforceWeekPrefix($summary, 'For this week, the student ');
        }
    }

    // 9. Build reference text for evaluation (using TextProcessingTrait)
    $referenceText = $this->buildReferenceText($activities, $learnings);
    
    // 10. Evaluate summary quality
    $evaluationResults = null;
    if (!empty($summary) && !empty($referenceText)) {
        $evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
        
        // Log ROUGE-1, ROUGE-2, ROUGE-L, BERT Score
        $this->evaluationService->logResults($evaluationResults, 'Coordinator Summary');
    }
```

### **Step 5: Return Response**
```php
    // 11. Merge and return all results
    return response()->json([
        'summary' => $summary,                    // From adapter
        'keywordScores' => $keywordScores,         // From adapter (word-based text mining)
        'usedGPT' => (bool) $summaryResult['usedGPT'],
        'evaluation' => $evaluationResults,        // ROUGE & BERT scores
        // PO Analysis data (from service)
        'pos_hit' => $poAnalysisResult['pos_hit'] ?? [],
        'pos_not_hit' => $poAnalysisResult['pos_not_hit'] ?? [],
        'po_word_hit' => $poAnalysisResult['po_word_hit'] ?? [],
        'po_context_hit' => $poAnalysisResult['po_context_hit'] ?? [],
        'recommendations' => $poAnalysisResult['recommendations'] ?? [],
        'corrected_activities' => $poAnalysisResult['corrected_activities'] ?? $activities,
        'corrected_learnings' => $poAnalysisResult['corrected_learnings'] ?? $learnings,
    ], 200);
}
```

---

## 📋 **CHAIRPERSON FLOW**

### **Step 1: Frontend Request → Controller**
**Route:** `POST /api/v1/summary/chair`  
**Controller:** `ChairpersonSummaryController::generate()`

```php
// File: app/Http/Controllers/Api/Chairperson/ChairpersonSummaryController.php

public function generate(Request $request, ChairSummaryAdapter $adapter): JsonResponse
{
    // 1. Extract request parameters
    $coordinatorId = $request->input('coordinatorId');
    $sectionId = $request->input('sectionId');
    $week = $request->integer('week');
    $useGPT = (bool) $request->input('useGPT');

    // 2. Query database for weekly entries
    $query = DB::table('weekly_entries as we')
        ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
        ->join('students as s', 's.id', '=', 'we.student_id');

    if ($coordinatorId) {
        $query->where('s.coordinator_id', $coordinatorId);
    }
    if ($sectionId) {
        $query->where('s.section_id', $sectionId);
    }
    if ($week) {
        $query->where('we.week_number', $week);
    }

    $rows = $query->get();

    // 3. Extract activities and learnings (using TextProcessingTrait)
    $extracted = $this->extractActivitiesAndLearnings($rows);
    $activities = $extracted['activities'];
    $learnings = $extracted['learnings'];

    // 4. Create data hash for caching
    $dataHash = hash('sha256', json_encode([
        'activities' => $activities,
        'learnings' => $learnings,
        'coordinator_id' => $coordinatorId,
        'section_id' => $sectionId,
        'week' => $week
    ]));

    // 5. Check cache
    $cached = DB::table('po_analysis_cache')
        ->where('coordinator_id', $coordinatorId)
        ->where('data_hash', $dataHash)
        ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
        ->when($week, fn($q) => $q->where('week_number', $week))
        ->first();

    if ($cached) {
        // Use cached results
        $result = [
            'summary' => $cached->summary,
            'pos_hit' => json_decode($cached->pos_hit, true) ?? [],
            'pos_not_hit' => json_decode($cached->pos_not_hit, true) ?? [],
            'poContextHit' => json_decode($cached->po_context_hit, true) ?? [],
            'poWordHit' => json_decode($cached->po_word_hit, true) ?? [],
            'recommendations' => json_decode($cached->recommendations, true) ?? [],
            'activities' => $activities,
            'learnings' => $learnings,
            'cached' => true,
        ];
    } else {
        // Generate new analysis
```

### **Step 2: Generate Summary + PO Analysis → Adapter**
**Next Process:** `ChairSummaryAdapter::summarize()`

```php
        // 6. Build combined text (using TextProcessingTrait)
        $combined = $this->buildCombinedText($rows);

        // 7. Convert to third-person (using TextProcessingTrait)
        $combined = $this->convertToThirdPerson($combined, 'the student', 'the students');

        // 8. Call adapter to generate summary + PO analysis
        $result = $adapter->summarize($combined, $week, $useGPT, $activities, $learnings);
```

**Inside Adapter (`ChairSummaryAdapter::summarize()`):**
```php
// File: app/Services/Chairperson/ChairSummaryAdapter.php

public function summarize(string $text, ?int $week, bool $useGPT = false, array $activities = [], array $learnings = []): array
{
    // 1. Clean text using OpenAIService
    $clean = $this->openAIService->cleanText($text);
    $summary = '';
    $usedGPT = false;

    // 2. Generate summary using OpenAI
    if (!empty($clean) && $this->openAIService->isAvailable()) {
        // Build prompt using ChairSummaryPromptBuilder
        $prompt = $this->promptBuilder->buildSummaryPrompt($activities, $learnings, '', 'overall_summary');
        
        // Call OpenAI API
        $response = $this->openAIService->call($prompt, [
            'model' => 'gpt-4o-mini',
            'max_tokens' => 3000,
            'temperature' => 0.2,
            'timeout' => 90,
        ]);
        
        if ($response['success'] && $response['content']) {
            $summary = $this->openAIService->cleanText($response['content']);
            if ($week) {
                $summary = $this->openAIService->enforceWeekPrefix($summary, "For week {$week}, those students ");
            } else {
                $summary = $this->openAIService->enforceWeekPrefix($summary, 'For this week, those students ');
            }
            $usedGPT = true;
        }
    }

    // 3. Generate PO analysis using ChairpersonSummaryService
    if ($useGPT && !empty($clean)) {
        $chairpersonSummaryService = app(ChairpersonSummaryService::class);
        $result = $chairpersonSummaryService->generateSummaryWithPOAnalysis($clean, $week, $activities, $learnings);

        // 4. Merge OpenAI summary with PO analysis
        $result['summary'] = $summary; // Use OpenAI-generated summary
        $result['corrected_activities'] = $activities;
        $result['corrected_learnings'] = $learnings;
        
        return $result;
    }
    
    return [
        'summary' => $summary,
        'usedGPT' => $usedGPT,
        'pos_hit' => [],
        'pos_not_hit' => [],
        // ... empty PO analysis
    ];
}
```

**Inside Service (`ChairpersonSummaryService::generateSummaryWithPOAnalysis()`):**
```php
// File: app/Services/Chairperson/ChairpersonSummaryService.php

public function generateSummaryWithPOAnalysis(
    string $text,
    ?int $week = null,
    array $activities = [],
    array $learnings = []
): array {
    // Same structure as CoordinatorSummaryService
    // Uses ChairpersonPOPromptBuilder instead
    
    $prompt = $this->promptBuilder->buildPOAnalysisPrompt($clean, $week, $activities, $learnings);
    $response = $this->openAIService->call($prompt, [
        'model' => 'gpt-4o-mini',
        'max_tokens' => 3000,
        'temperature' => 0.2,
        'timeout' => 90,
    ]);
    
    // Extract and return PO analysis...
}
```

### **Step 3: Cache Results**
```php
        // 9. Save to cache (if not cached)
        if ((!empty($activities) || !empty($learnings)) && !isset($result['error'])) {
            DB::table('po_analysis_cache')->insert([
                'coordinator_id' => $coordinatorId,
                'section_id' => $sectionId ?? null,
                'week_number' => $week ?? null,
                'data_hash' => $dataHash,
                'pos_hit' => json_encode($result['pos_hit'] ?? []),
                'pos_not_hit' => json_encode($result['pos_not_hit'] ?? []),
                'po_context_hit' => json_encode($result['poContextHit'] ?? []),
                'po_word_hit' => json_encode($result['poWordHit'] ?? []),
                'recommendations' => json_encode($result['recommendations'] ?? []),
                'summary' => $result['summary'] ?? null,
                'activities' => json_encode($activities),
                'learnings' => json_encode($learnings),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
```

### **Step 4: Evaluation → Evaluation Service**
```php
    // 10. Ensure summary formatting
    if (isset($result['summary'])) {
        $result['summary'] = $this->convertToThirdPerson($result['summary'], 'the student', 'the students');
        if (!empty($week)) {
            $result['summary'] = $this->openAIService->enforceWeekPrefix($result['summary'], 'For this week, those students ');
        }
    }

    // 11. Build reference text for evaluation
    $currentActivities = $result['activities'] ?? $activities;
    $currentLearnings = $result['learnings'] ?? $learnings;
    $referenceText = $this->buildReferenceText($currentActivities, $currentLearnings);
    
    // 12. Evaluate summary quality
    $evaluationResults = null;
    if (!empty($result['summary']) && !empty($referenceText)) {
        $evaluationResults = $this->evaluationService->evaluate($result['summary'], $referenceText);
        
        // Log ROUGE-1, ROUGE-2, ROUGE-L, BERT Score
        $this->evaluationService->logResults($evaluationResults, 'Chairperson Summary');
    }
```

### **Step 5: Return Response**
```php
    // 13. Return all results
    return response()->json([
        'summary' => $result['summary'],
        'pos_hit' => $result['pos_hit'] ?? [],
        'pos_not_hit' => $result['pos_not_hit'] ?? [],
        'poContextHit' => $result['poContextHit'] ?? [],
        'poWordHit' => $result['poWordHit'] ?? [],
        'recommendations' => $result['recommendations'] ?? [],
        'evaluation' => $evaluationResults, // ROUGE & BERT scores
        'cached' => $cached ? true : false,
    ], 200);
}
```

---

## 🔄 **PROCESS SUMMARY**

### **Coordinator Flow:**
1. **Controller** → Query DB → Extract data → Build combined text
2. **Adapter** → Generate summary (OpenAI) + Keyword matching (text mining)
3. **Service** → Generate PO analysis (OpenAI)
4. **Evaluation Service** → Calculate ROUGE & BERT scores
5. **Controller** → Merge results → Return JSON

### **Chairperson Flow:**
1. **Controller** → Query DB → Extract data → Check cache
2. **Adapter** → Generate summary (OpenAI)
3. **Service** → Generate PO analysis (OpenAI)
4. **Adapter** → Merge summary + PO analysis
5. **Controller** → Cache results → Evaluation → Return JSON

---

## 📝 **KEY DIFFERENCES**

| Feature | Coordinator | Chairperson |
|---------|------------|-------------|
| **Summary Generation** | `CoordinatorSummaryAdapter::analyze()` | `ChairSummaryAdapter::summarize()` |
| **PO Analysis** | `CoordinatorSummaryService` | `ChairpersonSummaryService` |
| **Prompt Builder** | `CoordinatorSummaryPromptBuilder` + `CoordinatorPOPromptBuilder` | `ChairSummaryPromptBuilder` + `ChairpersonPOPromptBuilder` |
| **Caching** | ❌ No caching | ✅ Uses `po_analysis_cache` table |
| **Keyword Matching** | ✅ Yes (text mining) | ❌ No (only context-based) |
| **Subject** | "the student" (singular) | "those students" (plural) |

---

## 🔗 **FILE LOCATIONS**

### **Controllers:**
- `app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php`
- `app/Http/Controllers/Api/Chairperson/ChairpersonSummaryController.php`

### **Adapters:**
- `app/Services/Coordinator/CoordinatorSummaryAdapter.php`
- `app/Services/Chairperson/ChairSummaryAdapter.php`

### **Services:**
- `app/Services/Coordinator/CoordinatorSummaryService.php`
- `app/Services/Chairperson/ChairpersonSummaryService.php`

### **Prompt Builders:**
- `app/Services/Coordinator/CoordinatorSummaryPromptBuilder.php`
- `app/Services/Coordinator/CoordinatorPOPromptBuilder.php`
- `app/Services/Chairperson/ChairSummaryPromptBuilder.php`
- `app/Services/Chairperson/ChairpersonPOPromptBuilder.php`

### **Shared:**
- `app/Services/OpenAI/OpenAIService.php` - OpenAI API calls
- `app/Services/OpenAI/SummaryEvaluationService.php` - ROUGE & BERT evaluation
- `app/Services/OpenAI/Traits/TextProcessingTrait.php` - Shared text processing methods

