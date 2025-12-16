# COORDINATOR SUMMARY FLOW

## 1. FRONTEND REQUEST

**File:** `siims-react-app/src/pages/ViewReportsPage.jsx`

**Line 20:** `week: weekValue,`

- Sends the week value from the frontend to the backend API.

**Line 21:** `useGPT: true,`

- Enables OpenAI processing for summary generation.

**Line 18-26:** POST request to `/api/v1/summary`

- Forwards `studentId`, `week`, `useGPT`, `analysisType`, and `isOverall` to the controller.

---

## 2. CONTROLLER - Database Fetch

**File:** `app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php`

**Line 58-100:** Database query
```php
$query = DB::table('weekly_entries as we')
    ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
    ->join('students as s', 's.id', '=', 'we.student_id');
```
- Fetches weekly entries (tasks and learnings) from the database for the specified student and week.

**Line 103-105:** Extract activities and learnings
```php
$extracted = $this->extractActivitiesAndLearnings($rows);
$activities = $extracted['activities'];
$learnings = $extracted['learnings'];
```
- Separates tasks into activities and learnings arrays.

**Line 108:** Build combined text
```php
$combined = $this->buildCombinedText($rows);
```
- Combines all tasks and learnings into a single text string.

**Line 111:** Convert to third person
```php
$combined = $this->convertToThirdPerson($combined);
```
- Converts first-person pronouns (I, me, my) to third person (the student).

---

## 3. ADAPTER - Summary Generation

**File:** `app/Services/Coordinator/CoordinatorSummaryAdapter.php`

**Line 41:** Clean text
```php
$clean = $this->openAIService->cleanText($text);
```
- Removes HTML tags, quotes, and normalizes whitespace.

**Line 101:** Build prompt
```php
$prompt = $this->coordinatorPromptBuilder->buildPrompt($activities, $learnings, '', $promptType);
```
- Builds the prompt for OpenAI with activities and learnings.

**Line 103-108:** Call OpenAI
```php
$response = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 250,
    'temperature' => 0.6,
    'timeout' => 120,
]);
```
- Calls OpenAI API to generate the summary.

**Line 111:** Clean response
```php
$summary = $this->openAIService->cleanText($response['content']);
```
- Cleans the OpenAI-generated summary.

**Line 165-169:** Return summary
```php
return [
    'summary' => $summary,
    'keywordScores' => $scores,
    'usedGPT' => $usedGPT,
];
```
- Returns the summary to the controller.

---

## 4. SERVICE - PO Analysis

**File:** `app/Services/Coordinator/CoordinatorSummaryService.php`

**Line 55:** Build PO analysis prompt
```php
$prompt = $this->promptBuilder->buildPOAnalysisPrompt($clean, $week, $activities, $learnings);
```
- Builds the prompt for PO analysis.

**Line 58-64:** Call OpenAI for PO analysis
```php
$response = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 2000,
    'temperature' => 0.2,
    'timeout' => 120,
]);
```
- Calls OpenAI to analyze which POs are met/not met.

**Line 70:** Extract PO arrays
```php
$pos = $this->extractPosArrays($rawContent);
```
- Extracts `pos_hit` and `pos_not_hit` from the JSON response.

**Line 71:** Extract PO hit types
```php
$poTypes = $this->extractPoHitTypes($rawContent);
```
- Extracts `po_word_hit` and `po_context_hit` for graph data.

**Line 72:** Extract recommendations
```php
$recommendations = $this->extractRecommendations($rawContent);
```
- Extracts recommendations from the JSON response.

**Line 94-101:** Ensure all 15 POs are accounted for
```php
$missingPOs = array_diff($allPOs, array_merge($hitPOs, $notHitPOs));
foreach ($missingPOs as $po) {
    $pos['notHit'][] = [
        'po' => $po,
        'reason' => $this->getDefaultNotHitReason($po)
    ];
}
```
- Adds any missing POs to `pos_not_hit` with default reasons.

**Line 104:** Ensure complete recommendations
```php
$recommendations = $this->ensureCompleteRecommendations($recommendations, $notHitPOs);
```
- Expands recommendation ranges (e.g., "PO6-PO9") and ensures one recommendation per not-met PO.

**Line 109-119:** Return PO analysis
```php
return [
    'pos_hit' => $pos['hit'],
    'pos_not_hit' => $pos['notHit'],
    'po_word_hit' => $poTypes['word'] ?? [],
    'po_context_hit' => $poTypes['context'] ?? [],
    'recommendations' => $recommendations,
];
```
- Returns PO analysis data to the controller.

---

## 5. OPENAI SERVICE - API Call

**File:** `app/Services/OpenAI/OpenAIService.php`

**Line 149:** POST to OpenAI API
```php
$response = $httpClient->post('https://api.openai.com/v1/chat/completions', [
    'model' => $config['model'],
    'messages' => $normalizedMessages,
    'temperature' => $config['temperature'],
    'max_tokens' => $config['max_tokens'],
]);
```
- Sends the request to OpenAI's API.

**Line 159:** Extract content
```php
$content = $data['choices'][0]['message']['content'] ?? null;
```
- Extracts the generated text from the response.

**Line 211-217:** Return result
```php
return [
    'success' => true,
    'content' => $content ? trim($content) : null,
    'error' => null,
    'raw' => $data,
    'usage' => $usage
];
```
- Returns the OpenAI response to the adapter/service.

---

## 6. EVALUATION - ROUGE & BERT Scores

**File:** `app/Services/OpenAI/SummaryEvaluationService.php`

**Line 20-21:** Normalize texts
```php
$summary = $this->normalizeText($generatedSummary);
$reference = $this->normalizeText($referenceText);
```
- Normalizes both texts for comparison.

**Line 24:** Calculate ROUGE-1
```php
$rouge1 = $this->calculateRouge1($summary, $reference);
```
- Calculates unigram overlap (word-level matching).

**Line 816-817:** Tokenize for ROUGE-1
```php
$summaryWords = $this->tokenize($summary);
$referenceWords = $this->tokenize($reference);
```
- Splits texts into words.

**Line 829-833:** Count overlapping words
```php
foreach ($summaryFreq as $word => $count) {
    if (isset($referenceFreq[$word])) {
        $overlap += min($count, $referenceFreq[$word]);
    }
}
```
- Counts matching words between summary and reference.

**Line 836-838:** Calculate precision, recall, F1
```php
$precision = count($summaryWords) > 0 ? $overlap / count($summaryWords) : 0.0;
$recall = count($referenceWords) > 0 ? $overlap / count($referenceWords) : 0.0;
$f1 = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0.0;
```
- Calculates ROUGE-1 metrics.

**Line 25:** Calculate ROUGE-2
```php
$rouge2 = $this->calculateRouge2($summary, $reference);
```
- Calculates bigram overlap (2-word phrase matching).

**Line 868-869:** Extract bigrams
```php
$summaryBigrams = $this->extractBigrams($summary);
$referenceBigrams = $this->extractBigrams($reference);
```
- Extracts 2-word sequences from both texts.

**Line 881-885:** Count overlapping bigrams
```php
foreach ($summaryFreq as $bigram => $count) {
    if (isset($referenceFreq[$bigram])) {
        $overlap += min($count, $referenceFreq[$bigram]);
    }
}
```
- Counts matching bigrams.

**Line 26:** Calculate ROUGE-L
```php
$rougeL = $this->calculateRougeL($summary, $reference);
```
- Calculates Longest Common Subsequence (sentence structure similarity).

**Line 926:** Calculate LCS length
```php
$lcsLength = $this->longestCommonSubsequence($summaryWords, $referenceWords);
```
- Finds the longest matching sequence of words.

**Line 29:** Calculate BERT Score
```php
$bertScore = $this->calculateBertScore($summary, $reference);
```
- Calculates semantic similarity using word weights.

**Line 968-969:** Calculate word weights
```php
$summaryWeights = $this->calculateWordWeights($summaryWords);
$referenceWeights = $this->calculateWordWeights($referenceWords);
```
- Assigns importance weights to words (TF-IDF-like).

**Line 975-986:** Calculate semantic similarity
```php
foreach ($summaryWords as $word) {
    $wordWeight = $summaryWeights[$word] ?? 0.0;
    $totalWeight += $wordWeight;
    
    if (in_array($word, $referenceWords)) {
        $totalScore += $wordWeight;
    } else {
        $similarity = $this->calculateWordSimilarity($word, $referenceWords);
        $totalScore += $wordWeight * $similarity;
    }
}
```
- Checks exact matches and semantic similarity (synonyms).

**Line 990:** Normalize BERT score
```php
$score = $totalWeight > 0 ? $totalScore / $totalWeight : 0.0;
```
- Normalizes the score to 0.0-1.0.

**Line 32-36:** Calculate overall average
```php
$overall = [
    'precision' => ($rouge1['precision'] + $rouge2['precision'] + $rougeL['precision'] + $bertScore) / 4,
    'recall' => ($rouge1['recall'] + $rouge2['recall'] + $rougeL['recall'] + $bertScore) / 4,
    'f1' => ($rouge1['f1'] + $rouge2['f1'] + $rougeL['f1'] + $bertScore) / 4,
];
```
- Averages all metrics for overall score.

---

## 7. CONTROLLER - Return Response

**File:** `app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php`

**Line 201:** Build reference text
```php
$referenceText = $this->buildReferenceText($activities, $learnings);
```
- Builds reference text from raw database data for evaluation.

**Line 213:** Evaluate summary
```php
$evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
```
- Compares OpenAI summary against raw database data.

**Line 268-285:** Return JSON response
```php
return response()->json([
    'summary' => $summary,
    'pos_hit' => $poAnalysisResult['pos_hit'] ?? [],
    'pos_not_hit' => $poAnalysisResult['pos_not_hit'] ?? [],
    'recommendations' => $recommendations,
    'evaluation' => $evaluationResults,
]);
```
- Returns all data to the frontend.

---

# CHAIRPERSON SUMMARY FLOW

## 1. FRONTEND REQUEST

**File:** `siims-react-app/src/components/chairperson/ChairpersonSummary.jsx`

**Line 50-52:** Set week parameter
```javascript
if (week && week !== "overall") {
  qp.set('week', String(week));
}
```
- Sets the week value if not "overall".

**Line 53:** Set useGPT
```javascript
qp.set('useGPT', '1');
```
- Enables OpenAI processing.

**Line 55:** GET request
```javascript
const resp = await fetch(`${apiBase}/api/v1/summary/chair?${qp.toString()}`);
```
- Forwards query parameters to the controller.

---

## 2. CONTROLLER - Database Fetch

**File:** `app/Http/Controllers/Api/Chairperson/ChairpersonSummaryController.php`

**Line 58-95:** Database query
```php
$query = DB::table('weekly_entries as we')
    ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
    ->join('students as s', 's.id', '=', 'we.student_id')
    ->whereNotNull('we.tasks')
    ->whereNotNull('we.learnings');
```
- Fetches weekly entries for all students under the coordinator.

**Line 125-127:** Extract activities and learnings
```php
$extracted = $this->extractActivitiesAndLearnings($rows);
$activities = $extracted['activities'];
$learnings = $extracted['learnings'];
```
- Separates tasks into activities and learnings arrays.

**Line 251:** Build combined text
```php
$combined = $this->buildCombinedText($rows);
```
- Combines all students' tasks and learnings.

**Line 254:** Convert to third person
```php
$combined = $this->convertToThirdPerson($combined, 'the student', 'the students');
```
- Converts to third person (plural for multiple students).

---

## 3. ADAPTER - Summary Generation

**File:** `app/Services/Chairperson/ChairSummaryAdapter.php`

**Line 243:** Clean text
```php
$clean = $this->openAIService->cleanText($text);
```
- Removes HTML tags and normalizes text.

**Line 252:** Build summary prompt
```php
$prompt = $this->promptBuilder->buildSummaryPrompt($activities, $learnings, '', $promptType);
```
- Builds the prompt for chairperson summary (multiple students).

**Line 253-258:** Call OpenAI for summary
```php
$response = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 4000,
    'temperature' => 0.2,
    'timeout' => 120,
]);
```
- Calls OpenAI to generate aggregated summary.

**Line 261:** Clean summary
```php
$summary = $this->openAIService->cleanText($response['content']);
```
- Cleans the generated summary.

**Line 291:** Call service for PO analysis
```php
$chairpersonSummaryService = app(ChairpersonSummaryService::class);
$result = $chairpersonSummaryService->generateSummaryWithPOAnalysis($clean, $week, $activities, $learnings);
```
- Calls service to generate PO analysis for all students.

**Line 296:** Merge summary
```php
$result['summary'] = $summary;
```
- Uses OpenAI-generated summary in the result.

---

## 4. SERVICE - PO Analysis

**File:** `app/Services/Chairperson/ChairpersonSummaryService.php`

**Line 55:** Build PO analysis prompt
```php
$prompt = $this->promptBuilder->buildPOAnalysisPrompt($clean, $week, $activities, $learnings);
```
- Builds the prompt for PO analysis (multiple students).

**Line 58-64:** Call OpenAI for PO analysis
```php
$response = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 4000,
    'temperature' => 0.2,
    'timeout' => 120,
]);
```
- Calls OpenAI to analyze POs for all students.

**Line 76:** Extract PO arrays
```php
$pos = $this->extractPosArrays($rawContent);
```
- Extracts `pos_hit` and `pos_not_hit` from JSON.

**Line 77:** Extract PO hit types
```php
$poTypes = $this->extractPoHitTypes($rawContent);
```
- Extracts `po_word_hit` and `po_context_hit` for graphs.

**Line 78:** Extract recommendations
```php
$recommendations = $this->extractRecommendations($rawContent);
```
- Extracts recommendations from JSON.

**Line 107-114:** Ensure all 15 POs are accounted for
```php
$missingPOs = array_diff($allPOs, array_merge($hitPOs, $notHitPOs));
foreach ($missingPOs as $po) {
    $pos['notHit'][] = [
        'po' => $po,
        'reason' => $this->getDefaultNotHitReason($po)
    ];
}
```
- Adds missing POs to `pos_not_hit`.

**Line 117:** Ensure complete recommendations
```php
$recommendations = $this->ensureCompleteRecommendations($recommendations, $notHitPOs);
```
- Expands ranges and ensures one recommendation per not-met PO.

**Line 122-132:** Return PO analysis
```php
return [
    'pos_hit' => $pos['hit'],
    'pos_not_hit' => $pos['notHit'],
    'po_word_hit' => $poTypes['word'] ?? [],
    'po_context_hit' => $poTypes['context'] ?? [],
    'recommendations' => $recommendations,
];
```
- Returns PO analysis to the adapter.

---

## 5. OPENAI SERVICE - API Call

Same as Coordinator (lines 149, 159, 211-217 in `OpenAIService.php`).

---

## 6. EVALUATION - ROUGE & BERT Scores

Same as Coordinator (lines 20-21, 24-26, 29, 32-36 in `SummaryEvaluationService.php`).

---

## 7. CONTROLLER - Return Response

**File:** `app/Http/Controllers/Api/Chairperson/ChairpersonSummaryController.php`

**Line 434:** Build reference text
```php
$referenceText = $this->buildReferenceText($currentActivities, $currentLearnings);
```
- Builds reference text from all students' data.

**Line 472:** Evaluate summary
```php
$evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
```
- Compares summary against raw database data.

**Line 516:** Add evaluation to result
```php
$result['evaluation'] = $evaluationResults;
```
- Adds evaluation metrics to the response.

**Line 546:** Return JSON response
```php
return response()->json($result, 200);
```
- Returns all data (summary, PO analysis, recommendations, evaluation) to the frontend.

