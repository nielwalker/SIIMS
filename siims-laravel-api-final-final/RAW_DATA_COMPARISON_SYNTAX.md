# Raw Data Fetching and OpenAI Summary Comparison Syntax

This document shows the complete syntax and flow of how raw data is fetched from the database and compared with OpenAI-generated summaries.

## Overview

The comparison process involves:
1. **Fetching raw data** from database (activities + learnings)
2. **Building reference text** from raw data (normalized)
3. **Generating summary** using OpenAI
4. **Comparing** OpenAI summary against reference text using evaluation metrics (ROUGE, BERT Score)

---

## 1. Fetch Raw Data from Database

**Explanation:** This is the first step in the comparison process. We need to retrieve the original data that was stored in the database before any AI processing. This raw data serves as the "ground truth" that we will compare against the AI-generated summary. We extract two main types of content: activities (tasks performed) and learnings (knowledge gained) from weekly entries. This step ensures we have the source material needed for accurate evaluation.

### Example: Coordinator Controller

```php
// File: app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php

// Fetch activities and learnings from database
$activities = [];
$learnings = [];

// From WeeklyEntry model or similar
foreach ($weeklyEntries as $entry) {
    if (!empty($entry->tasks)) {
        $activities[] = $entry->tasks;
    }
    if (!empty($entry->learnings)) {
        $learnings[] = $entry->learnings;
    }
}

// Or use extractActivitiesAndLearnings() from TextProcessingTrait
$extracted = $this->extractActivitiesAndLearnings($weeklyEntries);
$activities = $extracted['activities'];
$learnings = $extracted['learnings'];
```

### Example: Chairperson Controller

```php
// File: app/Http/Controllers/Api/Chairperson/ChairpersonSummaryController.php

// Fetch from database collection
$currentActivities = [];
$currentLearnings = [];

foreach ($weeklyEntries as $row) {
    if (!empty($row->tasks)) {
        $currentActivities[] = strip_tags($row->tasks);
    }
    if (!empty($row->learnings)) {
        $currentLearnings[] = strip_tags($row->learnings);
    }
}
```

---

## 2. Build Reference Text from Raw Data

### Syntax: `buildReferenceText()` Method

**Location:** `app/Services/OpenAI/Traits/TextProcessingTrait.php`

```php
/**
 * Build reference text from raw database data for evaluation
 * 
 * @param array|string $activities Raw activities from database
 * @param array|string $learnings Raw learnings from database
 * @param string $assessment Optional assessment text
 * @return string Combined reference text (normalized for better evaluation)
 */
protected function buildReferenceText($activities, $learnings, string $assessment = ''): string
{
    $parts = [];
    
    // Ensure activities is an array
    if (!is_array($activities)) {
        $activities = is_string($activities) && !empty($activities) ? [$activities] : [];
    }
    
    // Ensure learnings is an array
    if (!is_array($learnings)) {
        $learnings = is_string($learnings) && !empty($learnings) ? [$learnings] : [];
    }
    
    // Normalize and clean activities/learnings for better evaluation
    // Convert to third person to match summary format
    $normalizeForEvaluation = function($text) {
        if (empty($text)) return '';
        
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Convert first person to third person for better matching
        $text = preg_replace('/\bI\s+(did|worked|learned|completed|attended|participated)/i', 'the student $1', $text);
        $text = preg_replace('/\bI\s+(was|am|have|had)/i', 'the student $1', $text);
        $text = preg_replace('/\bmy\b/i', 'their', $text);
        $text = preg_replace('/\bme\b/i', 'the student', $text);
        $text = preg_replace('/\bwe\s+(did|worked|learned|completed)/i', 'the students $1', $text);
        $text = preg_replace('/\bour\b/i', 'their', $text);
        $text = preg_replace('/\bus\b/i', 'the students', $text);
        
        // Remove list markers and numbers
        $text = preg_replace('/^\d+[\.\)]\s*/m', '', $text);
        $text = preg_replace('/^[-•*]\s*/m', '', $text);
        
        // Clean up extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    };
    
    // Add activities (normalized)
    if (!empty($activities)) {
        $activitiesText = array_filter(array_map(function($a) use ($normalizeForEvaluation) {
            return $normalizeForEvaluation($a);
        }, $activities));
        if (!empty($activitiesText)) {
            $activitiesStr = implode('. ', array_filter($activitiesText));
            $parts[] = $activitiesStr;
        }
    }
    
    // Add learnings (normalized)
    if (!empty($learnings)) {
        $learningsText = array_filter(array_map(function($l) use ($normalizeForEvaluation) {
            return $normalizeForEvaluation($l);
        }, $learnings));
        if (!empty($learningsText)) {
            $learningsStr = implode('. ', array_filter($learningsText));
            $parts[] = $learningsStr;
        }
    }
    
    // Add assessment if provided
    if (!empty($assessment)) {
        $parts[] = trim($assessment);
    }
    
    // Join all parts naturally
    $referenceText = implode(' ', $parts);
    
    // Final cleanup
    $referenceText = preg_replace('/\s+/', ' ', $referenceText);
    $referenceText = trim($referenceText);
    
    return $referenceText;
}
```

### Usage Example:

```php
// In Controller or Service
$referenceText = $this->buildReferenceText($activities, $learnings, $assessment);

// Example output:
// "the student completed database queries. the student learned SQL optimization. 
//  the students participated in team meetings. the student gained experience with PostgreSQL."
```

---

## 3. Generate Summary Using OpenAI

### Syntax: Generate Summary

```php
// File: app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php

// Build prompt
$prompt = $this->coordinatorPromptBuilder->buildPrompt($activities, $learnings, $assessment);

// Call OpenAI API
$response = $this->openAIService->callSimple($prompt, 'gpt-4o-mini', 300, 0.6, 30);

// Get generated summary
if ($response['success']) {
    $summary = $this->openAIService->cleanText($response['summary']);
    
    // Ensure proper format
    if (!preg_match('/^For\s+this\s+week,\s+the\s+student/i', $summary)) {
        $summary = 'For this week, the student ' . ltrim($summary);
    }
}

// Example OpenAI Summary:
// "For this week, the student completed database queries and learned SQL optimization techniques. 
//  The student participated in team meetings and gained valuable experience with PostgreSQL."
```

---

## 4. Compare OpenAI Summary with Raw Data

### Syntax: Evaluation Method

**Location:** `app/Services/OpenAI/SummaryEvaluationService.php`

```php
/**
 * Evaluate summary against reference text (raw database data)
 * 
 * @param string $generatedSummary The OpenAI-generated summary to evaluate
 * @param string $referenceText The raw data from database (activities + learnings)
 * @return array{rouge1: array, rouge2: array, rougeL: array, bertScore: float, overall: array}
 */
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

---

## 5. Complete Example: Full Comparison Flow

### Coordinator Controller Example

```php
// File: app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php

use App\Services\OpenAI\SummaryEvaluationService;
use App\Services\OpenAI\Traits\TextProcessingTrait;

class CoordinatorSummaryController extends Controller
{
    use TextProcessingTrait;
    
    protected $evaluationService;
    
    public function __construct(SummaryEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }
    
    public function getSummary(Request $request)
    {
        // STEP 1: Fetch raw data from database
        $weeklyEntries = WeeklyEntry::where('student_id', $studentId)
            ->where('week', $week)
            ->get();
        
        $activities = [];
        $learnings = [];
        
        foreach ($weeklyEntries as $entry) {
            if (!empty($entry->tasks)) {
                $activities[] = $entry->tasks;
            }
            if (!empty($entry->learnings)) {
                $learnings[] = $entry->learnings;
            }
        }
        
        // STEP 2: Generate summary using OpenAI
        $prompt = $this->coordinatorPromptBuilder->buildPrompt($activities, $learnings, '');
        $response = $this->openAIService->callSimple($prompt, 'gpt-4o-mini', 300, 0.6, 30);
        
        if ($response['success']) {
            $summary = $this->openAIService->cleanText($response['summary']);
            
            // STEP 3: Build reference text from raw data
            $referenceText = $this->buildReferenceText($activities, $learnings);
            
            // STEP 4: Compare OpenAI summary with raw data
            Log::info('Starting summary evaluation', [
                'summary_length' => strlen($summary),
                'reference_length' => strlen($referenceText)
            ]);
            
            $evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
            
            // STEP 5: Log evaluation results
            $this->evaluationService->logResults($evaluationResults, 'Coordinator Summary');
            
            Log::info('Summary evaluation completed', [
                'rouge1_f1' => $evaluationResults['rouge1']['f1'],
                'rouge2_f1' => $evaluationResults['rouge2']['f1'],
                'rougeL_f1' => $evaluationResults['rougeL']['f1'],
                'bertScore' => $evaluationResults['bertScore']
            ]);
            
            return response()->json([
                'summary' => $summary,
                'evaluation' => $evaluationResults
            ]);
        }
    }
}
```

---

## 6. Evaluation Metrics Explained

### ROUGE-1 (Unigram Overlap)
- Measures word-level overlap
- Precision: How many summary words are in reference
- Recall: How many reference words are in summary
- F1: Harmonic mean of precision and recall

### ROUGE-2 (Bigram Overlap)
- Measures 2-word phrase overlap
- More strict than ROUGE-1

### ROUGE-L (Longest Common Subsequence)
- Measures sentence-level similarity
- Captures word order and structure

### BERT Score
- Measures semantic similarity
- Uses word embeddings and semantic matching

### Overall Score
- Average of all metrics
- Provides overall quality assessment

---

## 7. Example Output

```php
// Evaluation Results Structure
$evaluationResults = [
    'rouge1' => [
        'precision' => 0.75,
        'recall' => 0.82,
        'f1' => 0.78
    ],
    'rouge2' => [
        'precision' => 0.65,
        'recall' => 0.71,
        'f1' => 0.68
    ],
    'rougeL' => [
        'precision' => 0.73,
        'recall' => 0.80,
        'f1' => 0.76
    ],
    'bertScore' => 0.85,
    'overall' => [
        'precision' => 0.745,
        'recall' => 0.782,
        'f1' => 0.767
    ]
];
```

---

## Summary

The comparison process:
1. **Raw Data** → Fetch from database (activities + learnings)
2. **Reference Text** → Build normalized text from raw data
3. **OpenAI Summary** → Generate summary using OpenAI API
4. **Evaluation** → Compare summary vs reference using ROUGE/BERT metrics
5. **Results** → Log and return evaluation metrics

The evaluation helps ensure OpenAI summaries accurately reflect the raw data from the database.

