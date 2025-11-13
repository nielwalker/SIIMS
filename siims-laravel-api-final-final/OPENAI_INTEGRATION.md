# OpenAI Integration Documentation

## Overview

OpenAI integration generates summaries and performs Program Outcome (PO) analysis from student weekly reports. The system uses GPT-4o-mini model to process activities and learnings.

---

## 1. Frontend Request Syntax

### Coordinator Summary Request

**File:** `siims-react-app/src/pages/ViewReportsPage.jsx`  
**Lines:** 542-550

**Syntax:**
```javascript
const resp = await axiosClient.post("/api/v1/summary", {
  studentId: selectedStudentId,
  week: weekValue,              
  useGPT: true,                 
  analysisType: "coordinator",
  isOverall: isOverall,         
}, {
  timeout: 90000,               
});
const data = resp?.data || {};
```

**How it works:**
- Frontend sends POST request with student ID, week number, and analysis type
- Request includes `useGPT: true` to enable OpenAI processing
- For "overall" week, `week` is `null` and `isOverall` is `true`
- Response contains summary, PO analysis, and recommendations

**Alternative File:** `siims-react-app/src/pages/remotes/HomeRemotePage.jsx` (lines 120-126)

---

### Chairperson Summary Request

**File:** `siims-react-app/src/components/chairperson/ChairpersonSummary.jsx`  
**Lines:** 423-440

**Syntax:**
```javascript
const qp = new URLSearchParams();
if (coordinatorId) qp.set('coordinatorId', coordinatorId);
if (sectionId) qp.set('sectionId', String(sectionId));
if (week && week !== "overall") {
  qp.set('week', String(week));
}
qp.set('useGPT', '1');

const resp = await fetch(`${apiBase}/api/v1/summary/chair?${qp.toString()}`, {
  method: 'GET',
  headers: {
    Accept: "application/json",
    Authorization: `Bearer ${token}`,
    'X-Requested-With': 'XMLHttpRequest',
  },
  credentials: 'include',
});
const data = await resp.json();
```

**How it works:**
- Frontend sends GET request with query parameters
- Query params include `coordinatorId`, `sectionId`, `week`, and `useGPT: "1"`
- For "overall" week, `week` parameter is omitted
- Response contains aggregated summary and PO analysis for all students

**Alternative Files:**
- `siims-react-app/src/pages/ViewCoordinatorsPage.jsx` (line 743)
- `siims-react-app/src/pages/chairperson/ChairpersonViewCoordinatorPage.jsx` (line 95)

---

## 2. Controller Syntax

### Coordinator Summary Controller

**File:** `app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php`  
**Lines:** 43-295

**Syntax:**
```php
public function generate(Request $request, CoordinatorSummaryAdapter $adapter): JsonResponse
{
    set_time_limit(120); // 2 minutes timeout
    
    $studentId = $request->input('studentId');
    $week = $request->integer('week');
    $useGPT = (bool) $request->input('useGPT');
    $analysisType = $request->input('analysisType');
    $isOverall = $request->boolean('isOverall');
    
    // Fetch weekly entries from database
    $query = DB::table('weekly_entries as we')
        ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
        ->join('students as s', 's.id', '=', 'we.student_id');
    
    if ($studentId) {
        $query->where('we.student_id', $studentId);
    }
    
    // Extract activities and learnings
    $extracted = $this->extractActivitiesAndLearnings($rows);
    $activities = $extracted['activities'];
    $learnings = $extracted['learnings'];
    
    // Build combined text
    $combined = $this->buildCombinedText($rows);
    $combined = $this->convertToThirdPerson($combined);
    
    // Generate summary using adapter
    $summaryResult = $adapter->analyze($combined, $analysisType, $useGPT, $week);
    $summary = $summaryResult['summary'];
    
    // Generate PO analysis using service
    $poAnalysisResult = $this->coordinatorSummaryService->generateSummaryWithPOAnalysis(
        $combined, 
        $week, 
        $limitedActivities, 
        $limitedLearnings
    );
    
    // Evaluate summary
    $referenceText = $this->buildReferenceText($activities, $learnings);
    $evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
    
    return response()->json([
        'summary' => $summary,
        'pos_hit' => $poAnalysisResult['pos_hit'] ?? [],
        'pos_not_hit' => $poAnalysisResult['pos_not_hit'] ?? [],
        'recommendations' => $recommendations,
        'evaluation' => $evaluationResults,
    ]);
}
```

**How it works:**
- Controller receives request and extracts parameters (studentId, week, useGPT)
- Fetches weekly entries from database using query builder
- Extracts activities and learnings from database rows
- Calls adapter to generate summary using OpenAI
- Calls service to generate PO analysis using OpenAI
- Evaluates summary against raw database data
- Returns JSON response with summary, PO analysis, and evaluation metrics

---

### Chairperson Summary Controller

**File:** `app/Http/Controllers/Api/Chairperson/ChairpersonSummaryController.php`  
**Lines:** 46-588

**Syntax:**
```php
public function generate(Request $request, ChairSummaryAdapter $adapter): JsonResponse
{
    set_time_limit(120); // 2 minutes timeout
    
    $coordinatorId = $request->input('coordinatorId');
    $sectionId = $request->input('sectionId');
    $week = $request->integer('week');
    $useGPT = (bool) $request->input('useGPT');
    
    // Fetch weekly entries for all students under coordinator
    $query = DB::table('weekly_entries as we')
        ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
        ->join('students as s', 's.id', '=', 'we.student_id')
        ->whereNotNull('we.tasks')
        ->whereNotNull('we.learnings');
    
    if ($coordinatorId) {
        $query->where('s.coordinator_id', $coordinatorId);
    }
    if ($sectionId) {
        $query->where('s.section_id', $sectionId);
    }
    if ($week) {
        $query->where('we.week_number', $week);
    }
    
    // Extract activities and learnings
    $extracted = $this->extractActivitiesAndLearnings($rows);
    $activities = $extracted['activities'];
    $learnings = $extracted['learnings'];
    
    // Build combined text
    $combined = $this->buildCombinedText($rows);
    $combined = $this->convertToThirdPerson($combined);
    
    // Generate summary and PO analysis using adapter
    $result = $adapter->summarize($combined, $week, $useGPT, $activities, $learnings);
    
    return response()->json([
        'summary' => $result['summary'],
        'pos_hit' => $result['pos_hit'] ?? [],
        'pos_not_hit' => $result['pos_not_hit'] ?? [],
        'recommendations' => $result['recommendations'] ?? [],
    ]);
}
```

**How it works:**
- Controller receives GET request with query parameters (coordinatorId, sectionId, week, useGPT)
- Fetches weekly entries for all students under coordinator (and section if specified)
- Extracts activities and learnings from all students' entries
- Calls adapter to generate summary and PO analysis using OpenAI
- Returns JSON response with aggregated summary and PO analysis

---

## 3. Adapter Syntax

### Coordinator Summary Adapter

**File:** `app/Services/Coordinator/CoordinatorSummaryAdapter.php`  
**Lines:** 37-126

**Syntax:**
```php
public function analyze(string $text, ?string $analysisType, bool $useGPT = true, ?int $week = null): array
{
    // Clean text using OpenAIService
    $clean = $this->openAIService->cleanText($text);
    
    // Calculate keyword scores (for text mining)
    $keywordSets = [
        ['math', 'mathematics', 'science', 'algorithm', 'compute', 'analysis'],
        ['best practice', 'standard', 'policy', 'method', 'procedure', 'protocol'],
        // ... 15 PO keyword sets
    ];
    
    $lower = mb_strtolower($clean);
    $counts = array_map(function ($set) use ($lower) {
        $c = 0;
        foreach ($set as $kw) {
            if (str_contains($lower, $kw)) { $c++; }
        }
        return $c;
    }, $keywordSets);
    
    $total = array_sum($counts) ?: 1;
    $scores = array_map(fn($c) => (int)round(($c / $total) * 100), $counts);
    
    // Generate summary using OpenAI
    if ($clean && $useGPT && $this->openAIService->isAvailable()) {
        $prompt = $this->coordinatorPromptBuilder->buildPrompt($activities, $learnings, '');
        $response = $this->openAIService->callSimple($prompt, 'gpt-4o-mini', 250, 0.6, 25);
        
        if ($response['success']) {
            $summary = $this->openAIService->cleanText($response['summary'] ?? '');
            $usedGPT = true;
        }
    }
    
    return [
        'summary' => $summary,
        'keywordScores' => $scores,
        'usedGPT' => $usedGPT,
    ];
}
```

**How it works:**
- Adapter receives combined text and parameters
- Cleans text using OpenAIService
- Calculates keyword scores for 15 POs using text mining (word matching)
- Builds prompt using CoordinatorSummaryPromptBuilder
- Calls OpenAI API using OpenAIService.callSimple()
- Returns summary, keyword scores, and GPT usage flag

---

### Chairperson Summary Adapter

**File:** `app/Services/Chairperson/ChairSummaryAdapter.php`  
**Lines:** 240-361

**Syntax:**
```php
public function summarize(string $text, ?int $week, bool $useGPT = false, array $activities = [], array $learnings = []): array
{
    // Clean text using OpenAIService
    $clean = $this->openAIService->cleanText($text);
    
    // Generate summary using OpenAI
    if (!empty($clean) && $this->openAIService->isAvailable()) {
        $promptType = (!empty($week) && $week > 0) ? 'chair_week' : 'overall_summary';
        $prompt = $this->promptBuilder->buildSummaryPrompt($activities, $learnings, '', $promptType);
        $response = $this->openAIService->call($prompt, [
            'model' => 'gpt-4o-mini',
            'max_tokens' => 4000,
            'temperature' => 0.2,
            'timeout' => 60,
        ]);
        
        if ($response['success'] && $response['content']) {
            $summary = $this->openAIService->cleanText($response['content']);
            $summary = $this->openAIService->enforceWeekPrefix($summary, "For week {$week}, those students ");
            $usedGPT = true;
        }
    }
    
    // Generate PO analysis using ChairpersonSummaryService
    if ($useGPT && !empty($clean)) {
        $chairpersonSummaryService = app(ChairpersonSummaryService::class);
        $result = $chairpersonSummaryService->generateSummaryWithPOAnalysis($clean, $week, $activities, $learnings);
        $result['summary'] = $summary; // Use OpenAI-generated summary
        return $result;
    }
    
    return [
        'summary' => $summary,
        'usedGPT' => $usedGPT,
        'pos_hit' => [],
        'pos_not_hit' => [],
        'recommendations' => [],
    ];
}
```

**How it works:**
- Adapter receives combined text, week, activities, and learnings
- Cleans text using OpenAIService
- Builds prompt using ChairSummaryPromptBuilder (different for weekly vs overall)
- Calls OpenAI API using OpenAIService.call() for summary generation
- Calls ChairpersonSummaryService for PO analysis
- Merges summary and PO analysis results
- Returns combined result with summary, PO analysis, and recommendations

---

## 4. Service Syntax

### Coordinator Summary Service

**File:** `app/Services/Coordinator/CoordinatorSummaryService.php`  
**Lines:** 36-133

**Syntax:**
```php
public function generateSummaryWithPOAnalysis(
    string $text,
    ?int $week = null,
    array $activities = [],
    array $learnings = []
): array {
    // Check if OpenAI is available
    if (!$this->openAIService->isAvailable()) {
        return $this->getUnavailableResponse();
    }
    
    // Clean text
    $clean = $this->openAIService->cleanText($text);
    if (empty($clean)) {
        return $this->getUnavailableResponse();
    }
    
    // Build PO analysis prompt
    $prompt = $this->promptBuilder->buildPOAnalysisPrompt($clean, $week, $activities, $learnings);
    
    // Call OpenAI API
    $response = $this->openAIService->call($prompt, [
        'model' => 'gpt-4o-mini',
        'max_tokens' => 2000,
        'temperature' => 0.2,
        'timeout' => 45,
        'top_p' => 0.95,
    ]);
    
    if ($response['success'] && $response['content']) {
        $rawContent = $response['content'];
        
        // Extract PO analysis from JSON response
        $pos = $this->extractPosArrays($rawContent);
        $poTypes = $this->extractPoHitTypes($rawContent);
        $recommendations = $this->extractRecommendations($rawContent);
        
        // Ensure all 15 POs are accounted for
        $allPOs = array_map(function($i) {
            return 'PO' . ($i + 1);
        }, range(0, 14));
        
        // Find missing POs and add to pos_not_hit
        $missingPOs = array_diff($allPOs, array_merge($hitPOs, $notHitPOs));
        foreach ($missingPOs as $po) {
            $pos['notHit'][] = [
                'po' => $po,
                'reason' => $this->getDefaultNotHitReason($po)
            ];
        }
        
        // Ensure complete recommendations
        $recommendations = $this->ensureCompleteRecommendations($recommendations, $notHitPOs);
        
        return [
            'pos_hit' => $pos['hit'],
            'pos_not_hit' => $pos['notHit'],
            'po_word_hit' => $poTypes['word'] ?? [],
            'po_context_hit' => $poTypes['context'] ?? [],
            'recommendations' => $recommendations,
        ];
    }
    
    return $this->getUnavailableResponse();
}
```

**How it works:**
- Service receives text, week, activities, and learnings
- Checks if OpenAI is available
- Builds PO analysis prompt using CoordinatorPOPromptBuilder
- Calls OpenAI API using OpenAIService.call()
- Extracts PO analysis from JSON response (pos_hit, pos_not_hit, recommendations)
- Ensures all 15 POs are accounted for (adds missing POs to pos_not_hit)
- Expands recommendation ranges (e.g., "PO6-PO9") and ensures one recommendation per not-met PO
- Returns PO analysis array with pos_hit, pos_not_hit, po_word_hit, po_context_hit, and recommendations

**Helper Methods:**
- `extractPosArrays()` (lines 158-171): Extracts pos_hit and pos_not_hit from JSON
- `extractPoHitTypes()` (lines 176-189): Extracts po_word_hit and po_context_hit from JSON
- `extractRecommendations()` (lines 194-204): Extracts recommendations from JSON
- `ensureCompleteRecommendations()` (lines 255-379): Expands ranges and ensures one recommendation per PO
- `validateRecommendationsFromOpenAI()` (lines 389-427): Validates recommendations from OpenAI

---

### Chairperson Summary Service

**File:** `app/Services/Chairperson/ChairpersonSummaryService.php`  
**Lines:** 36-146

**Syntax:**
```php
public function generateSummaryWithPOAnalysis(
    string $text,
    ?int $week = null,
    array $activities = [],
    array $learnings = []
): array {
    // Check if OpenAI is available
    if (!$this->openAIService->isAvailable()) {
        return $this->getUnavailableResponse();
    }
    
    // Clean text
    $clean = $this->openAIService->cleanText($text);
    if (empty($clean)) {
        return $this->getUnavailableResponse();
    }
    
    // Build PO analysis prompt
    $prompt = $this->promptBuilder->buildPOAnalysisPrompt($clean, $week, $activities, $learnings);
    
    // Call OpenAI API
    $response = $this->openAIService->call($prompt, [
        'model' => 'gpt-4o-mini',
        'max_tokens' => 4000,
        'temperature' => 0.2,
        'timeout' => 60,
        'top_p' => 0.95,
    ]);
    
    if ($response['success'] && $response['content']) {
        $rawContent = $response['content'];
        
        // Extract PO analysis from JSON response
        $pos = $this->extractPosArrays($rawContent);
        $poTypes = $this->extractPoHitTypes($rawContent);
        $recommendations = $this->extractRecommendations($rawContent);
        
        // Ensure all 15 POs are accounted for
        $allPOs = array_map(function($i) {
            return 'PO' . ($i + 1);
        }, range(0, 14));
        
        // Find missing POs and add to pos_not_hit
        $missingPOs = array_diff($allPOs, array_merge($hitPOs, $notHitPOs));
        foreach ($missingPOs as $po) {
            $pos['notHit'][] = [
                'po' => $po,
                'reason' => $this->getDefaultNotHitReason($po)
            ];
        }
        
        // Ensure complete recommendations
        $recommendations = $this->ensureCompleteRecommendations($recommendations, $notHitPOs);
        
        return [
            'pos_hit' => $pos['hit'],
            'pos_not_hit' => $pos['notHit'],
            'po_word_hit' => $poTypes['word'] ?? [],
            'po_context_hit' => $poTypes['context'] ?? [],
            'recommendations' => $recommendations,
        ];
    }
    
    return $this->getUnavailableResponse();
}
```

**How it works:**
- Service receives text, week, activities, and learnings (aggregated from all students)
- Checks if OpenAI is available
- Builds PO analysis prompt using ChairpersonPOPromptBuilder (includes instructions for multiple students)
- Calls OpenAI API using OpenAIService.call() with higher max_tokens (4000) for larger datasets
- Extracts PO analysis from JSON response
- Ensures all 15 POs are accounted for
- Expands recommendation ranges and ensures one recommendation per not-met PO
- Returns PO analysis array with pos_hit, pos_not_hit, po_word_hit, po_context_hit, and recommendations

---

## 5. OpenAI API Service Syntax

**File:** `app/Services/OpenAI/OpenAIService.php`  
**Lines:** 31-290

### Main Call Method

**Lines:** 31-132

**Syntax:**
```php
public function call($messages, array $options = []): array
{
    try {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            return [
                'success' => false,
                'content' => null,
                'error' => 'API key not configured',
                'raw' => null
            ];
        }
        
        // Normalize messages format
        $normalizedMessages = $this->normalizeMessages($messages);
        
        // Merge options with defaults
        $config = array_merge([
            'model' => self::DEFAULT_MODEL,           // 'gpt-4o-mini'
            'max_tokens' => self::DEFAULT_MAX_TOKENS, // 4000
            'temperature' => self::DEFAULT_TEMPERATURE, // 0.2
            'timeout' => self::DEFAULT_TIMEOUT,       // 60
            'top_p' => 0.95,
        ], $options);
        
        // Configure HTTP client
        $httpClient = Http::withToken($apiKey)
            ->timeout($config['timeout']);
        
        // Disable SSL verification for development
        if (app()->environment(['local', 'development', 'testing'])) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }
        
        // Call OpenAI API
        $response = $httpClient->post('https://api.openai.com/v1/chat/completions', [
            'model' => $config['model'],
            'messages' => $normalizedMessages,
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
            'top_p' => $config['top_p'] ?? 0.95,
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            $usage = $data['usage'] ?? null;
            
            // Log token usage
            if ($usage) {
                Log::info('OpenAI Token Usage', [
                    'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                ]);
            }
            
            return [
                'success' => true,
                'content' => $content ? trim($content) : null,
                'error' => null,
                'raw' => $data,
                'usage' => $usage
            ];
        }
        
        return [
            'success' => false,
            'content' => null,
            'error' => 'API call failed',
            'raw' => $response->json()
        ];
    } catch (\Exception $e) {
        Log::error('OpenAI API Exception', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'content' => null,
            'error' => $e->getMessage(),
            'raw' => null
        ];
    }
}
```

**How it works:**
- Receives messages (string or array) and options (model, max_tokens, temperature, timeout)
- Normalizes messages to OpenAI format (array of role/content pairs)
- Merges options with defaults (model: gpt-4o-mini, max_tokens: 4000, temperature: 0.2, timeout: 60)
- Configures HTTP client with API key and timeout
- Disables SSL verification for development environments
- Makes POST request to `https://api.openai.com/v1/chat/completions`
- Extracts content from response (choices[0].message.content)
- Logs token usage (prompt_tokens, completion_tokens, total_tokens)
- Returns array with success, content, error, raw, and usage

---

### Simple Call Method

**Lines:** 144-164

**Syntax:**
```php
public function callSimple(
    string $prompt,
    string $model = 'gpt-4o-mini',
    int $maxTokens = 500,
    float $temperature = 0.7,
    int $timeout = 30
): array {
    $result = $this->call($prompt, [
        'model' => $model,
        'max_tokens' => $maxTokens,
        'temperature' => $temperature,
        'timeout' => $timeout,
    ]);
    
    return [
        'success' => $result['success'],
        'summary' => $result['content'] ?? '',
        'error' => $result['error'],
        'usage' => $result['usage'] ?? null
    ];
}
```

**How it works:**
- Wrapper method for simple prompt calls
- Receives prompt string and optional parameters
- Calls main `call()` method with normalized options
- Returns simplified array with success, summary, error, and usage

---

### Text Cleaning Method

**Lines:** 213-228

**Syntax:**
```php
public function cleanText($text): string
{
    if (!is_string($text)) {
        return '';
    }
    
    // Strip HTML tags and normalize whitespace
    $text = strip_tags($text);
    return trim(
        preg_replace(
            '/\s+/',
            ' ',
            str_replace(['"', "'", '\\"', "\\'"], '', $text)
        )
    );
}
```

**How it works:**
- Removes HTML tags using `strip_tags()`
- Removes quotes (single, double, escaped)
- Normalizes whitespace (multiple spaces to single space)
- Trims leading/trailing whitespace
- Returns cleaned text string

---

### Week Prefix Enforcement

**Lines:** 254-279

**Syntax:**
```php
public function enforceWeekPrefix(string $text, string $prefix = 'For this week, those students '): string
{
    $t = trim($text);
    if ($t === '') {
        return $prefix . 'completed their weekly activities and learning outcomes.';
    }
    
    // Check if already starts with the desired phrase
    $escapedPrefix = preg_quote($prefix, '/');
    if (preg_match('/^' . $escapedPrefix . '/i', $t)) {
        return $t;
    }
    
    // Remove any existing week prefixes to avoid duplication
    $t = preg_replace('/^For\s+(this\s+week|week\s+\d+),\s+those\s+students\s+/i', '', $t);
    $t = preg_replace('/^For\s+overall,\s+the\s+students\s+/i', '', $t);
    
    return $prefix . ltrim($t);
}
```

**How it works:**
- Ensures summary starts with correct week prefix
- Checks if text already has the prefix (avoids duplication)
- Removes existing week prefixes if present
- Adds correct prefix based on week (coordinator: "For this week, the student", chairperson: "For week X, those students")
- Returns formatted text with proper prefix

---

### Availability Check

**Lines:** 286-289

**Syntax:**
```php
public function isAvailable(): bool
{
    return !empty($this->getApiKey());
}
```

**How it works:**
- Checks if OpenAI API key is configured
- Returns true if API key exists, false otherwise
- Used to check availability before making API calls

---

## 6. Evaluation Syntax

**File:** `app/Services/OpenAI/SummaryEvaluationService.php`  
**Lines:** 28-572

### Main Evaluate Method

**Lines:** 28-56

**Syntax:**
```php
public function evaluate(string $generatedSummary, string $referenceText): array
{
    // Clean and normalize both texts for fair comparison
    $summary = $this->normalizeText($generatedSummary);
    $reference = $this->normalizeText($referenceText);
    
    // Calculate ROUGE scores
    $rouge1 = $this->calculateRouge1($summary, $reference);
    $rouge2 = $this->calculateRouge2($summary, $reference);
    $rougeL = $this->calculateRougeL($summary, $reference);
    
    // Calculate BERT Score (simplified semantic similarity)
    $bertScore = $this->calculateBertScore($summary, $reference);
    
    // Calculate overall average score
    $overall = [
        'precision' => ($rouge1['precision'] + $rouge2['precision'] + $rougeL['precision'] + $bertScore) / 4,
        'recall' => ($rouge1['recall'] + $rouge2['recall'] + $rougeL['recall'] + $bertScore) / 4,
        'f1' => ($rouge1['f1'] + $rouge2['f1'] + $rougeL['f1'] + $bertScore) / 4,
    ];
    
    return [
        'rouge1' => $rouge1,
        'rouge2' => $rouge2,
        'rougeL' => $rougeL,
        'bertScore' => $bertScore,
        'overall' => $overall,
    ];
}
```

**How it works:**
- Receives generated summary (from OpenAI) and reference text (from database)
- Normalizes both texts (converts to lowercase, removes HTML, normalizes pronouns)
- Calculates ROUGE-1 (unigram overlap), ROUGE-2 (bigram overlap), ROUGE-L (LCS)
- Calculates BERT Score (semantic similarity using word embeddings)
- Calculates overall average scores (precision, recall, F1)
- Returns evaluation metrics array

---

### ROUGE-1 Calculation

**Lines:** 112-149

**Syntax:**
```php
private function calculateRouge1(string $summary, string $reference): array
{
    // Split into words (unigrams)
    $summaryWords = $this->tokenize($summary);
    $referenceWords = $this->tokenize($reference);
    
    if (empty($summaryWords) || empty($referenceWords)) {
        return ['precision' => 0.0, 'recall' => 0.0, 'f1' => 0.0];
    }
    
    // Count word frequencies
    $summaryFreq = array_count_values($summaryWords);
    $referenceFreq = array_count_values($referenceWords);
    
    // Count overlapping unigrams (minimum frequency in both)
    $overlap = 0;
    foreach ($summaryFreq as $word => $count) {
        if (isset($referenceFreq[$word])) {
            $overlap += min($count, $referenceFreq[$word]);
        }
    }
    
    // Calculate precision, recall, and F1
    $precision = count($summaryWords) > 0 ? $overlap / count($summaryWords) : 0.0;
    $recall = count($referenceWords) > 0 ? $overlap / count($referenceWords) : 0.0;
    $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0.0;
    
    return [
        'precision' => round($precision, 4),
        'recall' => round($recall, 4),
        'f1' => round($f1, 4),
    ];
}
```

**How it works:**
- Tokenizes summary and reference into words (unigrams)
- Counts word frequencies in both texts
- Calculates overlap (minimum frequency of each word in both texts)
- Calculates precision (overlap / total words in summary)
- Calculates recall (overlap / total words in reference)
- Calculates F1 score (harmonic mean of precision and recall)
- Returns precision, recall, and F1 scores

---

### ROUGE-2 Calculation

**Lines:** 166-198

**Syntax:**
```php
private function calculateRouge2(string $summary, string $reference): array
{
    // Extract bigrams (2-word sequences)
    $summaryBigrams = $this->extractBigrams($summary);
    $referenceBigrams = $this->extractBigrams($reference);
    
    if (empty($summaryBigrams) || empty($referenceBigrams)) {
        return ['precision' => 0.0, 'recall' => 0.0, 'f1' => 0.0];
    }
    
    // Count bigram frequencies
    $summaryFreq = array_count_values($summaryBigrams);
    $referenceFreq = array_count_values($referenceBigrams);
    
    // Count overlapping bigrams
    $overlap = 0;
    foreach ($summaryFreq as $bigram => $count) {
        if (isset($referenceFreq[$bigram])) {
            $overlap += min($count, $referenceFreq[$bigram]);
        }
    }
    
    // Calculate precision, recall, and F1
    $precision = count($summaryBigrams) > 0 ? $overlap / count($summaryBigrams) : 0.0;
    $recall = count($referenceBigrams) > 0 ? $overlap / count($referenceBigrams) : 0.0;
    $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0.0;
    
    return [
        'precision' => round($precision, 4),
        'recall' => round($recall, 4),
        'f1' => round($f1, 4),
    ];
}
```

**How it works:**
- Extracts bigrams (2-word sequences) from summary and reference
- Counts bigram frequencies in both texts
- Calculates overlap (minimum frequency of each bigram in both texts)
- Calculates precision, recall, and F1 scores (same as ROUGE-1)
- Returns precision, recall, and F1 scores

---

### ROUGE-L Calculation

**Lines:** 216-239

**Syntax:**
```php
private function calculateRougeL(string $summary, string $reference): array
{
    // Split into words
    $summaryWords = $this->tokenize($summary);
    $referenceWords = $this->tokenize($reference);
    
    if (empty($summaryWords) || empty($referenceWords)) {
        return ['precision' => 0.0, 'recall' => 0.0, 'f1' => 0.0];
    }
    
    // Calculate Longest Common Subsequence (LCS) length
    $lcsLength = $this->longestCommonSubsequence($summaryWords, $referenceWords);
    
    // Calculate precision, recall, and F1
    $precision = count($summaryWords) > 0 ? $lcsLength / count($summaryWords) : 0.0;
    $recall = count($referenceWords) > 0 ? $lcsLength / count($referenceWords) : 0.0;
    $f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0.0;
    
    return [
        'precision' => round($precision, 4),
        'recall' => round($recall, 4),
        'f1' => round($f1, 4),
    ];
}
```

**How it works:**
- Tokenizes summary and reference into words
- Calculates Longest Common Subsequence (LCS) using dynamic programming
- Calculates precision (LCS length / total words in summary)
- Calculates recall (LCS length / total words in reference)
- Calculates F1 score (harmonic mean of precision and recall)
- Returns precision, recall, and F1 scores

---

### BERT Score Calculation

**Lines:** 257-293

**Syntax:**
```php
private function calculateBertScore(string $summary, string $reference): float
{
    // Tokenize both texts
    $summaryWords = $this->tokenize($summary);
    $referenceWords = $this->tokenize($reference);
    
    if (empty($summaryWords) || empty($referenceWords)) {
        return 0.0;
    }
    
    // Calculate word importance using TF-IDF-like weighting
    $summaryWeights = $this->calculateWordWeights($summaryWords);
    $referenceWeights = $this->calculateWordWeights($referenceWords);
    
    // Calculate semantic similarity score
    $totalScore = 0.0;
    $totalWeight = 0.0;
    
    foreach ($summaryWords as $word) {
        $wordWeight = $summaryWeights[$word] ?? 0.0;
        $totalWeight += $wordWeight;
        
        // Check if word exists in reference (exact match)
        if (in_array($word, $referenceWords)) {
            $totalScore += $wordWeight;
        } else {
            // Check for semantic similarity (synonyms, related words)
            $similarity = $this->calculateWordSimilarity($word, $referenceWords);
            $totalScore += $wordWeight * $similarity;
        }
    }
    
    // Normalize score
    $score = $totalWeight > 0 ? $totalScore / $totalWeight : 0.0;
    
    return round($score, 4);
}
```

**How it works:**
- Tokenizes summary and reference into words
- Calculates word weights using TF-IDF-like approach (important words get higher weights)
- For each word in summary, checks if it exists in reference (exact match)
- If not found, calculates semantic similarity using word stem matching and synonyms
- Calculates weighted score (word weight × similarity)
- Normalizes score (total score / total weight)
- Returns BERT Score (0.0 to 1.0)

---

### Log Results Method

**Lines:** 496-571

**Syntax:**
```php
public function logResults(array $evaluationResults, string $context = 'Summary Evaluation'): void
{
    $rouge1 = $evaluationResults['rouge1'];
    $rouge2 = $evaluationResults['rouge2'];
    $rougeL = $evaluationResults['rougeL'];
    $bertScore = $evaluationResults['bertScore'];
    $overall = $evaluationResults['overall'];
    
    // Format console output
    $output = "\n" . str_repeat('=', 80) . "\n";
    $output .= "  {$context} - Evaluation Metrics\n";
    $output .= str_repeat('=', 80) . "\n";
    $output .= "  ROUGE-1 (Unigram Overlap):\n";
    $output .= "    Precision: " . number_format($rouge1['precision'] * 100, 2) . "%\n";
    $output .= "    Recall:    " . number_format($rouge1['recall'] * 100, 2) . "%\n";
    $output .= "    F1 Score:  " . number_format($rouge1['f1'] * 100, 2) . "%\n";
    // ... (similar for ROUGE-2, ROUGE-L, BERT Score, Overall)
    $output .= str_repeat('=', 80) . "\n";
    
    // Log to Laravel log
    Log::info("{$context} - Evaluation Metrics", [
        'rouge1' => $rouge1,
        'rouge2' => $rouge2,
        'rougeL' => $rougeL,
        'bertScore' => $bertScore,
        'overall' => $overall,
    ]);
    
    // Output to console
    error_log($output);
    if (php_sapi_name() === 'cli') {
        echo $output;
    }
}
```

**How it works:**
- Receives evaluation results and context (e.g., "Coordinator Summary")
- Formats evaluation metrics as human-readable text
- Logs structured data to Laravel log
- Outputs formatted text to console (error_log, stdout)
- Used for debugging and quality assurance

---

## 7. Traits Syntax

**File:** `app/Services/OpenAI/Traits/TextProcessingTrait.php`  
**Lines:** 11-228

### Convert to Third Person

**Lines:** 21-78

**Syntax:**
```php
protected function convertToThirdPerson(string $text, string $singularSubject = 'the student', string $pluralSubject = 'the students'): string
{
    if (!is_string($text) || $text === '') {
        return $text;
    }
    
    $replacements = [
        // First-person singular
        '/\bI\'m\b/i' => $singularSubject . ' is',
        '/\bI\'ve\b/i' => $singularSubject . ' has',
        '/\bI was\b/i' => $singularSubject . ' was',
        '/\bI am\b/i' => $singularSubject . ' is',
        '/\bI have\b/i' => $singularSubject . ' has',
        '/\bI\b/i' => $singularSubject,
        '/\bme\b/i' => $singularSubject,
        '/\bmy\b/i' => $singularSubject . '\'s',
        
        // First-person plural
        '/\bwe\'re\b/i' => $pluralSubject . ' are',
        '/\bwe\'ve\b/i' => $pluralSubject . ' have',
        '/\bwe were\b/i' => $pluralSubject . ' were',
        '/\bwe are\b/i' => $pluralSubject . ' are',
        '/\bwe have\b/i' => $pluralSubject . ' have',
        '/\bwe\b/i' => $pluralSubject,
        '/\bus\b/i' => $pluralSubject,
        '/\bour\b/i' => $pluralSubject . '\'',
    ];
    
    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }
    
    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text;
}
```

**How it works:**
- Converts first-person text (I, me, my, we, us, our) to third-person (the student, the students)
- Uses regex patterns to match first-person pronouns and contractions
- Replaces with singular or plural subject based on context
- Normalizes whitespace (multiple spaces to single space)
- Returns third-person text

---

### Build Reference Text

**Lines:** 89-167

**Syntax:**
```php
protected function buildReferenceText($activities, $learnings, string $assessment = ''): string
{
    $parts = [];
    
    // Ensure activities and learnings are arrays
    if (!is_array($activities)) {
        $activities = is_string($activities) && !empty($activities) ? [$activities] : [];
    }
    if (!is_array($learnings)) {
        $learnings = is_string($learnings) && !empty($learnings) ? [$learnings] : [];
    }
    
    // Normalize and clean activities/learnings for better evaluation
    $normalizeForEvaluation = function($text) {
        if (empty($text)) return '';
        
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Convert first person to third person for better matching
        $text = preg_replace('/\bI\s+(did|worked|learned|completed)/i', 'the student $1', $text);
        $text = preg_replace('/\bmy\b/i', 'their', $text);
        $text = preg_replace('/\bwe\s+(did|worked|learned)/i', 'the students $1', $text);
        
        // Remove list markers and numbers
        $text = preg_replace('/^\d+[\.\)]\s*/m', '', $text);
        $text = preg_replace('/^[-•*]\s*/m', '', $text);
        
        // Clean up extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    };
    
    // Add activities (normalized)
    if (!empty($activities)) {
        $activitiesText = array_filter(array_map($normalizeForEvaluation, $activities));
        if (!empty($activitiesText)) {
            $parts[] = implode('. ', array_filter($activitiesText));
        }
    }
    
    // Add learnings (normalized)
    if (!empty($learnings)) {
        $learningsText = array_filter(array_map($normalizeForEvaluation, $learnings));
        if (!empty($learningsText)) {
            $parts[] = implode('. ', array_filter($learningsText));
        }
    }
    
    // Add assessment if provided
    if (!empty($assessment)) {
        $parts[] = trim($assessment);
    }
    
    // Join all parts
    $referenceText = implode(' ', $parts);
    $referenceText = preg_replace('/\s+/', ' ', $referenceText);
    $referenceText = trim($referenceText);
    
    return $referenceText;
}
```

**How it works:**
- Builds reference text from raw database data (activities and learnings)
- Normalizes activities and learnings (converts to third person, removes HTML, removes list markers)
- Joins activities and learnings into single text
- Used for evaluation (comparing OpenAI summary against raw database data)
- Returns normalized reference text

---

### Extract Activities and Learnings

**Lines:** 175-207

**Syntax:**
```php
protected function extractActivitiesAndLearnings($rows): array
{
    $activities = [];
    $learnings = [];
    
    foreach ($rows as $row) {
        if (!empty($row->tasks)) {
            $cleanTasks = strip_tags($row->tasks);
            $cleanTasks = preg_replace('/\s+/', ' ', $cleanTasks);
            $cleanTasks = trim($cleanTasks);
            if (!empty($cleanTasks)) {
                $activities[] = $cleanTasks;
            }
        }
        if (!empty($row->learnings)) {
            $cleanLearnings = strip_tags($row->learnings);
            $cleanLearnings = preg_replace('/\s+/', ' ', $cleanLearnings);
            $cleanLearnings = trim($cleanLearnings);
            if (!empty($cleanLearnings)) {
                $learnings[] = $cleanLearnings;
            }
        }
    }
    
    // Remove duplicates
    $activities = array_values(array_unique(array_filter($activities)));
    $learnings = array_values(array_unique(array_filter($learnings)));
    
    return [
        'activities' => $activities,
        'learnings' => $learnings,
    ];
}
```

**How it works:**
- Extracts activities (tasks) and learnings from database rows
- Cleans each activity and learning (removes HTML, normalizes whitespace)
- Filters out empty values
- Removes duplicates
- Returns array with activities and learnings arrays

---

### Build Combined Text

**Lines:** 215-227

**Syntax:**
```php
protected function buildCombinedText($rows): string
{
    $combined = $rows->map(function ($r) {
        $t = trim(($r->tasks ?? '') . ' ' . ($r->learnings ?? ''));
        $t = preg_replace('/\s+/', ' ', $t);
        if ($t && !preg_match('/[.!?]$/', $t)) {
            $t .= '.';
        }
        return $t;
    })->filter()->implode(' ');
    
    return $combined;
}
```

**How it works:**
- Combines tasks and learnings from database rows into single text
- Adds period if text doesn't end with punctuation
- Filters out empty values
- Joins all rows with spaces
- Returns combined text string

---

## Configuration

- **Model:** `gpt-4o-mini`
- **Max Tokens:** 4000 (default), 2000-3000 (varies by endpoint)
- **Temperature:** 0.2
- **Timeout:** 60 seconds (default), 25-45 seconds (varies by endpoint)
- **API Key:** `OPENAI_API_KEY` environment variable

---

## Token Usage (Per Request)

### Coordinator Summary
- **Input:** 450-1,250 tokens
- **Output:** 50-150 tokens

### Coordinator PO Analysis
- **Input:** 2,700-3,500 tokens
- **Output:** 500-1,000 tokens

### Chairperson Summary
- **Input:** 1,350-3,350 tokens
- **Output:** 200-500 tokens

### Chairperson PO Analysis
- **Input:** 4,100-6,100 tokens
- **Output:** 800-1,500 tokens

---

## Flow Diagram

```
Frontend Request
    ↓
Controller (fetch data, extract activities/learnings)
    ↓
Adapter (generate summary using OpenAI)
    ↓
Service (generate PO analysis using OpenAI)
    ↓
OpenAIService (call OpenAI API)
    ↓
Evaluation Service (compare summary vs raw data)
    ↓
Response to Frontend
```
