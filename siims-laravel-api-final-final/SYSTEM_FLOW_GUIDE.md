# SIIMS System Flow Guide - Coordinator to Chairperson

## Overview

This document explains the step-by-step flow of how the system works from **Student → Coordinator → Chairperson**, including where to navigate in the codebase and the logic behind each step.

---

## Table of Contents

1. [System Architecture Overview](#system-architecture-overview)
2. [Student Weekly Report Submission](#student-weekly-report-submission)
3. [Coordinator View and Analysis](#coordinator-view-and-analysis)
4. [Chairperson View and Analysis](#chairperson-view-and-analysis)
5. [Summary Generation Flow](#summary-generation-flow)
6. [PO Analysis Flow](#po-analysis-flow)
7. [Evaluation Metrics (ROUGE & BERT)](#evaluation-metrics-rouge--bert)

---

## System Architecture Overview

### Key Components

1. **Frontend (React)**: `siims-react-app/src/`
2. **Backend (Laravel)**: `siims-laravel-api-final-final/`
3. **OpenAI Services**: `app/Services/OpenAI/`
4. **Controllers**: `app/Http/Controllers/Api/`

### Database Tables

- `students` - Student information
- `coordinators` - Coordinator information
- `weekly_entries` - Student weekly reports (activities, learnings)
- `po_analysis_cache` - Cached PO analysis results

---

## 1. Student Weekly Report Submission

### Flow

1. **Student submits weekly report** → Frontend: `StudentWeeklyAccomplishmentPage.jsx`
2. **Data saved to database** → Backend: `WeeklyEntryController.php`
3. **Report stored in `weekly_entries` table** with:
   - `tasks` (activities)
   - `learnings`
   - `week_number`
   - `student_id`

### Code Navigation

**Frontend:**
- `siims-react-app/src/pages/student/StudentWeeklyAccomplishmentPage.jsx`
- `siims-react-app/src/components/student/WeeklyRecordModalForm.jsx`

**Backend:**
- `app/Http/Controllers/WeeklyEntryController.php`
- Route: `POST /api/v1/weekly-entries`

### Logic

- Students can submit up to **5 reports per week**
- Reports are validated before saving
- Each report contains activities (tasks) and learnings

---

## 2. Coordinator View and Analysis

### Flow

1. **Coordinator logs in** → Views assigned students
2. **Selects a student** → Frontend: `ViewReportsPage.jsx`
3. **Selects a week** → Fetches weekly reports for that student
4. **System generates summary** → Uses OpenAI for summarization
5. **System analyzes PO coverage** → Uses OpenAI for PO analysis

### Code Navigation

**Frontend:**
- `siims-react-app/src/pages/ViewReportsPage.jsx` (Coordinator's student reports page)

**Backend:**
- `app/Http/Controllers/Api/SummaryController.php` → `generate()` method
- `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php` → `summarize()` method
- `app/Http/Controllers/Api/ChairSummaryController.php` → `generateForStudent()` method (for PO analysis)

**Routes:**
- `POST /api/v1/summary` (for summary generation)
- `GET /api/v1/summary/student-po-analysis` (for PO analysis)

### Step-by-Step Logic

#### Step 1: Coordinator Selects Student and Week

```javascript
// Frontend: ViewReportsPage.jsx
const fetchSummary = async () => {
  const resp = await axiosClient.post("/api/v1/summary", {
    studentId: selectedStudentId,
    week: selectedWeek,
    useGPT: true, // Use OpenAI for summarization
    analysisType: "coordinator",
  });
};
```

#### Step 2: Backend Fetches Weekly Entries

```php
// Backend: SummaryController.php
$query = DB::table('weekly_entries as we')
    ->select('we.week_number', 'we.tasks as activities', 'we.learnings')
    ->join('students as s', 's.id', '=', 'we.student_id')
    ->where('we.student_id', $studentId)
    ->where('we.week_number', $week);
```

#### Step 3: Summary Generation (OpenAI)

```php
// Backend: SummaryController.php → SummaryAdapter.php
// Calls OpenAI endpoint for summarization
$response = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 3000,
]);
```

#### Step 4: PO Analysis (OpenAI)

```php
// Backend: ChairSummaryController.php → generateForStudent()
// Calls OpenAI for PO analysis
$result = $chairSummaryService->generateSummaryWithPOAnalysis(
    $combinedText, 
    $week, 
    $activities, 
    $learnings
);
```

#### Step 5: Evaluation Metrics Calculated

```php
// Backend: SummaryEvaluationService.php
$evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
// Calculates ROUGE-1, ROUGE-2, ROUGE-L, and BERT Score
```

### What Coordinator Sees

- **Summary**: OpenAI-generated summary of student's weekly activities and learnings
- **PO Analysis**: List of Program Outcomes (POs) achieved and not met
- **PO Graph**: Visual representation of PO coverage with word-based and context-based contributions
- **Recommendations**: AI-generated suggestions for improvement

---

## 3. Chairperson View and Analysis

### Flow

1. **Chairperson logs in** → Views coordinators and students
2. **Selects a coordinator** → Frontend: `ChairpersonViewCoordinatorPage.jsx`
3. **Selects a section (optional)** → Filters students by section
4. **Selects a week or "Overall"** → Fetches all students' reports
5. **System generates aggregated summary** → Uses OpenAI for summarization
6. **System analyzes PO coverage** → Uses OpenAI for PO analysis across all students

### Code Navigation

**Frontend:**
- `siims-react-app/src/pages/chairperson/ChairpersonViewCoordinatorPage.jsx`
- `siims-react-app/src/components/chairperson/ChairpersonSummary.jsx`

**Backend:**
- `app/Http/Controllers/Api/ChairSummaryController.php` → `generate()` method
- `app/Services/ChairSummaryAdapter.php` → `summarize()` method
- `app/Services/OpenAI/ChairSummaryService.php` → `generateSummaryWithPOAnalysis()` method

**Routes:**
- `GET /api/v1/summary/chair` (for chairperson summary and PO analysis)

### Step-by-Step Logic

#### Step 1: Chairperson Selects Coordinator and Week

```javascript
// Frontend: ChairpersonSummary.jsx
const resp = await fetch(`${apiBase}/api/v1/summary/chair?${qp.toString()}`, {
  method: 'GET',
  headers: authHeaders,
});
```

#### Step 2: Backend Fetches All Students' Weekly Entries

```php
// Backend: ChairSummaryController.php
$query = DB::table('weekly_entries as we')
    ->select('we.week_number', 'we.tasks', 'we.learnings')
    ->join('students as s', 's.id', '=', 'we.student_id')
    ->where('s.coordinator_id', $coordinatorId);
    
if ($sectionId) {
    $query->where('s.section_id', $sectionId);
}
if ($week) {
    $query->where('we.week_number', $week);
}
```

#### Step 3: Extract Activities and Learnings

```php
// Backend: ChairSummaryController.php
$activities = [];
$learnings = [];

foreach ($rows as $row) {
    if (!empty($row->tasks)) {
        $activities[] = strip_tags($row->tasks);
    }
    if (!empty($row->learnings)) {
        $learnings[] = strip_tags($row->learnings);
    }
}
```

#### Step 4: Check Cache for PO Analysis

```php
// Backend: ChairSummaryController.php
$dataHash = hash('sha256', json_encode([
    'activities' => $activities,
    'learnings' => $learnings,
    'coordinator_id' => $coordinatorId,
    'section_id' => $sectionId,
    'week' => $week
]));

$cached = DB::table('po_analysis_cache')
    ->where('coordinator_id', $coordinatorId)
    ->where('data_hash', $dataHash)
    ->first();
```

#### Step 5: Generate Summary (OpenAI)

```php
// Backend: ChairSummaryAdapter.php
// Calls OpenAI for summary generation
$summary = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 3000,
]);
```

#### Step 6: Generate PO Analysis (OpenAI)

```php
// Backend: ChairSummaryService.php
$result = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 3000,
]);
// Extracts: pos_hit, pos_not_hit, po_word_hit, po_context_hit, recommendations
```

#### Step 7: Evaluation Metrics Calculated

```php
// Backend: ChairSummaryController.php
$evaluationResults = $this->evaluationService->evaluate($summary, $referenceText);
// Logs ROUGE and BERT scores to Laravel log
```

### What Chairperson Sees

- **Aggregated Summary**: OpenAI-generated summary of all students' weekly activities and learnings
- **PO Analysis**: List of Program Outcomes achieved and not met across all students
- **PO Graph**: Visual representation of PO coverage with nested bars (word-based vs context-based)
- **Recommendations**: AI-generated suggestions for coordinators and students
- **Analytics**: PO coverage percentage per coordinator

---

## 4. Summary Generation Flow

### OpenAI Summarization Process

#### For Coordinator

1. **Input**: Student's weekly activities and learnings
2. **Process**: 
   - `SummaryController.php` → `generate()`
   - `SummaryAdapter.php` → `analyze()`
   - `OpenAI/CoordinatorSummaryController.php` → `summarize()`
   - `OpenAIService.php` → `call()` → OpenAI API
3. **Output**: Summary in third-person format (e.g., "For this week, the student...")

#### For Chairperson

1. **Input**: All students' weekly activities and learnings (aggregated)
2. **Process**:
   - `ChairSummaryController.php` → `generate()`
   - `ChairSummaryAdapter.php` → `summarize()`
   - `OpenAI/SummaryController.php` → `summarize()`
   - `OpenAIService.php` → `call()` → OpenAI API
3. **Output**: Aggregated summary in third-person format (e.g., "For this week, those students...")

### Code Files

**Controllers:**
- `app/Http/Controllers/Api/SummaryController.php`
- `app/Http/Controllers/Api/ChairSummaryController.php`
- `app/Http/Controllers/Api/OpenAI/SummaryController.php`
- `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php`

**Services:**
- `app/Services/SummaryAdapter.php`
- `app/Services/ChairSummaryAdapter.php`
- `app/Services/OpenAI/OpenAIService.php`
- `app/Services/OpenAI/PromptBuilder.php`
- `app/Services/OpenAI/CoordinatorPromptBuilder.php`

---

## 5. PO Analysis Flow

### Program Outcome (PO) Analysis Process

#### Step 1: Extract Activities and Learnings

```php
// Backend: ChairSummaryController.php
$activities = []; // All activities from weekly entries
$learnings = [];  // All learnings from weekly entries
```

#### Step 2: Build OpenAI Prompt

```php
// Backend: ChairSummaryPromptBuilder.php
$prompt = $this->buildPOAnalysisPrompt($text, $week, $activities, $learnings);
// Prompt includes:
// - PO definitions (PO1-PO15)
// - Activities and learnings
// - Instructions to identify achieved and not-met POs
```

#### Step 3: Call OpenAI API

```php
// Backend: ChairSummaryService.php
$response = $this->openAIService->call($prompt, [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 3000,
    'temperature' => 0.2,
]);
```

#### Step 4: Parse OpenAI Response

```php
// Backend: ChairSummaryService.php
$json = json_decode($response['content'], true);
// Extracts:
// - pos_hit: Array of achieved POs with reasons
// - pos_not_hit: Array of not-met POs with reasons
// - po_word_hit: POs identified by keyword matching
// - po_context_hit: POs identified by contextual analysis
// - recommendations: AI-generated suggestions
```

#### Step 5: Hybrid Scoring

```javascript
// Frontend: ChairpersonSummary.jsx
// Combines word-based (40%) and context-based (60%) scores
const finalScore = (wordScore * 0.4) + (contextScore * 0.6);
```

### Code Files

**Services:**
- `app/Services/OpenAI/ChairSummaryService.php`
- `app/Services/OpenAI/ChairSummaryPromptBuilder.php`
- `app/Services/ChairSummaryAdapter.php`

**Frontend:**
- `siims-react-app/src/components/chairperson/ChairpersonSummary.jsx` (PO graph and scoring)

---

## 6. Evaluation Metrics (ROUGE & BERT)

### Evaluation Process

#### Step 1: Build Reference Text

```php
// Backend: ChairSummaryController.php
$referenceText = $this->buildReferenceText($activities, $learnings);
// Combines all activities and learnings from database
```

#### Step 2: Evaluate Summary

```php
// Backend: SummaryEvaluationService.php
$evaluationResults = $this->evaluate($summary, $referenceText);
// Calculates:
// - ROUGE-1: Unigram overlap (word-level)
// - ROUGE-2: Bigram overlap (phrase-level)
// - ROUGE-L: Longest Common Subsequence (sentence-level)
// - BERT Score: Semantic similarity
```

#### Step 3: Log Results

```php
// Backend: SummaryEvaluationService.php
$this->logResults($evaluationResults, 'Chairperson Summary');
// Logs to: storage/logs/laravel.log
```

### Where to View Results

**Laravel Log File:**
- Location: `storage/logs/laravel.log`
- Search for: "Evaluation Metrics" or "ROUGE"

**Example Output:**
```
================================================================================
  Chairperson Summary - Evaluation Metrics
================================================================================
  ROUGE-1 (Unigram Overlap):
    Precision: 75.50%
    Recall:    68.30%
    F1 Score:  71.70%

  ROUGE-2 (Bigram Overlap):
    Precision: 62.40%
    Recall:    55.20%
    F1 Score:  58.60%

  ROUGE-L (Longest Common Subsequence):
    Precision: 70.10%
    Recall:    63.50%
    F1 Score:  66.70%

  BERT Score (Semantic Similarity):
    Score:     78.90%

  Overall Average:
    Precision: 71.98%
    Recall:    64.23%
    F1 Score:  68.98%
================================================================================
```

### Code Files

**Service:**
- `app/Services/OpenAI/SummaryEvaluationService.php`

**Controllers (where evaluation is called):**
- `app/Http/Controllers/Api/SummaryController.php`
- `app/Http/Controllers/Api/ChairSummaryController.php`
- `app/Http/Controllers/Api/OpenAI/SummaryController.php`
- `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php`

---

## Quick Navigation Guide

### When Asked: "How does a student's report reach the chairperson?"

1. **Student submits** → `StudentWeeklyAccomplishmentPage.jsx`
2. **Saved to database** → `WeeklyEntryController.php`
3. **Coordinator views** → `ViewReportsPage.jsx` → `SummaryController.php`
4. **Chairperson views** → `ChairpersonSummary.jsx` → `ChairSummaryController.php`

### When Asked: "Where is the summary generated?"

- **Coordinator Summary**: `OpenAI/CoordinatorSummaryController.php` → `summarize()`
- **Chairperson Summary**: `OpenAI/SummaryController.php` → `summarize()`
- **OpenAI Service**: `OpenAI/OpenAIService.php` → `call()`

### When Asked: "Where is PO analysis done?"

- **Service**: `OpenAI/ChairSummaryService.php` → `generateSummaryWithPOAnalysis()`
- **Prompt Builder**: `OpenAI/ChairSummaryPromptBuilder.php` → `buildPOAnalysisPrompt()`
- **Adapter**: `ChairSummaryAdapter.php` → `summarize()`

### When Asked: "Where are evaluation metrics calculated?"

- **Service**: `OpenAI/SummaryEvaluationService.php` → `evaluate()`
- **Called from**: `SummaryController.php`, `ChairSummaryController.php`
- **Results logged to**: `storage/logs/laravel.log`

---

## Key Concepts

### 1. Hybrid Scoring

- **Word-based (40%)**: Keyword matching for PO identification
- **Context-based (60%)**: OpenAI contextual analysis for PO identification
- **Final Score**: Weighted combination of both

### 2. Caching

- PO analysis results are cached in `po_analysis_cache` table
- Cache key: SHA256 hash of activities, learnings, coordinator_id, section_id, week
- Prevents redundant OpenAI API calls

### 3. Third-Person Conversion

- All summaries are converted to third-person format
- "I learned" → "the student learned"
- "We did" → "the students did"

### 4. Evaluation Metrics

- **ROUGE-1**: Measures word overlap
- **ROUGE-2**: Measures phrase overlap
- **ROUGE-L**: Measures sentence structure similarity
- **BERT Score**: Measures semantic similarity

---

## Troubleshooting

### If summary is not generated:

1. Check OpenAI API key: `.env` → `OPENAI_API_KEY`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Check if `useGPT` parameter is `true` in API request

### If PO analysis is missing:

1. Check if OpenAI is available: `OpenAIService.php` → `isAvailable()`
2. Check cache: `po_analysis_cache` table
3. Check Laravel logs for errors

### If evaluation metrics are not showing:

1. Check Laravel log file: `storage/logs/laravel.log`
2. Search for "Evaluation Metrics" or "ROUGE"
3. Verify evaluation service is being called in controllers

---

## Summary

This system follows a clear flow:

1. **Student** → Submits weekly reports
2. **Coordinator** → Views individual student summaries and PO analysis
3. **Chairperson** → Views aggregated summaries and PO analysis across all students
4. **OpenAI** → Generates summaries and performs PO analysis
5. **Evaluation** → Calculates ROUGE and BERT scores to measure accuracy

All code is organized in:
- **Controllers**: Handle HTTP requests
- **Services**: Contain business logic
- **Adapters**: Bridge between controllers and services
- **OpenAI Services**: Centralized OpenAI API calls

