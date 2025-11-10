# OpenAI Code Structure - Brief Explanation

## 📁 File Structure Overview

```
app/
├── Http/Controllers/Api/
│   ├── SummaryController.php          ← Main controller for coordinator summaries
│   └── ChairSummaryController.php     ← Main controller for chairperson summaries
│
├── Http/Controllers/Api/OpenAI/
│   ├── SummaryController.php          ← OpenAI endpoint for chairperson summaries
│   └── CoordinatorSummaryController.php ← OpenAI endpoint for coordinator summaries
│
└── Services/
    ├── SummaryAdapter.php             ← Adapter for coordinator summaries
    ├── ChairSummaryAdapter.php        ← Adapter for chairperson summaries
    │
    └── OpenAI/
        ├── OpenAIService.php          ← Core service: Makes actual API calls to OpenAI
        ├── PromptBuilder.php          ← Builds prompts for chairperson
        ├── CoordinatorPromptBuilder.php ← Builds prompts for coordinator
        ├── ChairSummaryPromptBuilder.php ← Builds prompts for PO analysis
        ├── ChairSummaryService.php    ← Service for PO analysis only
        └── SummaryEvaluationService.php ← Calculates ROUGE & BERT scores
```

---

## 🔑 Key Components Explained

### 1. **Controllers** (`app/Http/Controllers/Api/`)
**What they do**: Handle HTTP requests from frontend, fetch data from database, call adapters

- **`SummaryController.php`** = Handles coordinator summary requests
  - Receives: `studentId`, `week`, `analysisType`, `useGPT`
  - Fetches: Weekly entries from database
  - Does: Word-based keyword matching (text mining)
  - Calls: `SummaryAdapter` for OpenAI summarization
  - Returns: Summary + keyword scores + evaluation metrics

- **`ChairSummaryController.php`** = Handles chairperson summary requests
  - Receives: `coordinatorId`, `sectionId`, `week`, `useGPT`
  - Fetches: All students' weekly entries under coordinator
  - Does: Checks cache, extracts activities/learnings
  - Calls: `ChairSummaryAdapter` for summary + PO analysis
  - Returns: Summary + PO analysis + evaluation metrics

---

### 2. **Adapters** (`app/Services/`)
**What they do**: Bridge between controllers and OpenAI services. Prepare data and format responses.

- **`SummaryAdapter.php`** = For coordinator summaries
  - Receives: Combined text (activities + learnings)
  - Does: Keyword matching (word-based text mining)
  - Calls: `OpenAIService` via `CoordinatorPromptBuilder` or `PromptBuilder`
  - Returns: Summary + keyword scores

- **`ChairSummaryAdapter.php`** = For chairperson summaries + PO analysis
  - Receives: Combined text, activities array, learnings array
  - Does: 
    1. Calls `OpenAIService` for summary generation
    2. Calls `ChairSummaryService` for PO analysis
  - Returns: Summary + PO analysis (pos_hit, pos_not_hit, recommendations)

---

### 3. **OpenAI Services** (`app/Services/OpenAI/`)

#### **`OpenAIService.php`** = Core Service
**What it does**: Makes actual HTTP requests to OpenAI API
- Has methods: `call()`, `callSimple()`, `isAvailable()`, `cleanText()`
- Handles: API authentication, error handling, token usage logging
- Returns: `['success' => bool, 'content' => string, 'error' => string]`

#### **`PromptBuilder.php`** = Builds prompts for chairperson summaries
**What it does**: Creates the text sent to OpenAI for summarization
- Method: `buildSummaryPrompt($activities, $learnings, $assessment, $type)`
- Returns: Formatted prompt string

#### **`CoordinatorPromptBuilder.php`** = Builds prompts for coordinator summaries
**What it does**: Creates prompts specifically for coordinator view
- Method: `buildPrompt($activities, $learnings, $assessment)`
- Returns: Formatted prompt string

#### **`ChairSummaryPromptBuilder.php`** = Builds prompts for PO analysis
**What it does**: Creates prompts for Program Outcome analysis
- Method: `buildPOAnalysisPrompt($text, $week, $activities, $learnings)`
- Returns: Detailed prompt with PO definitions and instructions

#### **`ChairSummaryService.php`** = Service for PO analysis
**What it does**: Handles PO analysis ONLY (not summary generation)
- Method: `generateSummaryWithPOAnalysis($text, $week, $activities, $learnings)`
- Calls: `OpenAIService` with `ChairSummaryPromptBuilder`
- Returns: PO analysis (pos_hit, pos_not_hit, po_word_hit, po_context_hit, recommendations)

#### **`SummaryEvaluationService.php`** = Evaluates summary quality
**What it does**: Calculates ROUGE-1, ROUGE-2, ROUGE-L, and BERT Score
- Method: `evaluate($candidateSummary, $referenceText)`
- Compares: Generated summary vs raw database data
- Returns: Evaluation metrics (precision, recall, F1 scores)

---

### 4. **OpenAI Controllers** (`app/Http/Controllers/Api/OpenAI/`)
**What they do**: Direct endpoints for OpenAI summarization (legacy/alternative routes)

- **`SummaryController.php`** = Direct OpenAI endpoint for chairperson
- **`CoordinatorSummaryController.php`** = Direct OpenAI endpoint for coordinator

---

## 🔄 Data Flow: How Data is Received

### **Flow 1: Coordinator Summary Request**

```
Frontend (React)
    ↓ POST /api/v1/summary
    { studentId: 123, week: 5, useGPT: true, analysisType: "coordinator" }
    ↓
SummaryController.php
    ↓
    1. Receives request parameters
    2. Queries database: SELECT * FROM weekly_entries WHERE student_id = 123 AND week_number = 5
    3. Combines activities + learnings into text
    4. Does keyword matching (word-based text mining)
    ↓
SummaryAdapter.php
    ↓
    1. Receives combined text
    2. Calls CoordinatorPromptBuilder → builds prompt
    3. Calls OpenAIService.callSimple() → sends to OpenAI API
    ↓
OpenAIService.php
    ↓
    1. Makes HTTP POST to https://api.openai.com/v1/chat/completions
    2. Receives JSON response from OpenAI
    3. Extracts summary text
    4. Logs token usage
    ↓
SummaryAdapter.php
    ↓
    1. Cleans and formats summary
    2. Returns: { summary, keywordScores, usedGPT }
    ↓
SummaryController.php
    ↓
    1. Calls SummaryEvaluationService → calculates ROUGE/BERT scores
    2. Returns JSON response to frontend
    ↓
Frontend receives:
{
    summary: "For week 5, the student...",
    keywordScores: [75, 60, 80, ...],
    usedGPT: true,
    evaluation: { rouge1: {...}, rouge2: {...}, ... }
}
```

---

### **Flow 2: Chairperson Summary + PO Analysis Request**

```
Frontend (React)
    ↓ GET /api/v1/summary/chair?coordinatorId=1&week=5&useGPT=true
    ↓
ChairSummaryController.php
    ↓
    1. Receives request parameters
    2. Queries database: SELECT * FROM weekly_entries 
       JOIN students WHERE coordinator_id = 1 AND week_number = 5
    3. Extracts activities[] and learnings[] arrays
    4. Checks cache (po_analysis_cache table)
    ↓
    If cached: Returns cached result
    If not cached:
    ↓
ChairSummaryAdapter.php
    ↓
    1. Calls OpenAIService for SUMMARY:
       - Uses PromptBuilder.buildSummaryPrompt()
       - Gets summary text
    ↓
    2. Calls ChairSummaryService for PO ANALYSIS:
       - Uses ChairSummaryPromptBuilder.buildPOAnalysisPrompt()
       - Gets PO analysis (pos_hit, pos_not_hit, recommendations)
    ↓
ChairSummaryService.php
    ↓
    1. Calls OpenAIService.call() with PO analysis prompt
    2. Parses JSON response from OpenAI
    3. Extracts: pos_hit, pos_not_hit, po_word_hit, po_context_hit
    ↓
ChairSummaryAdapter.php
    ↓
    1. Merges summary + PO analysis
    2. Returns complete result
    ↓
ChairSummaryController.php
    ↓
    1. Saves to cache (po_analysis_cache table)
    2. Calls SummaryEvaluationService → calculates ROUGE/BERT scores
    3. Returns JSON response to frontend
    ↓
Frontend receives:
{
    summary: "For week 5, those students...",
    pos_hit: [{"po": "PO1", "reason": "..."}, ...],
    pos_not_hit: [{"po": "PO2", "reason": "..."}, ...],
    po_word_hit: ["PO1", "PO3"],
    po_context_hit: ["PO5", "PO7"],
    recommendations: ["...", ...],
    evaluation: { rouge1: {...}, ... }
}
```

---

## 📊 Key Data Structures

### **Request Data (from Frontend)**
```php
// Coordinator
{
    studentId: 123,
    week: 5,
    useGPT: true,
    analysisType: "coordinator"
}

// Chairperson
{
    coordinatorId: 1,
    sectionId: 2,  // optional
    week: 5,       // or null for "overall"
    useGPT: true
}
```

### **Database Data (from weekly_entries table)**
```php
[
    {
        weekNumber: 5,
        activities: "Developed web application...",
        learnings: "Learned React hooks..."
    },
    ...
]
```

### **OpenAI Response**
```php
// Summary response
{
    success: true,
    content: "For week 5, the student developed...",
    usage: {
        prompt_tokens: 500,
        completion_tokens: 200,
        total_tokens: 700
    }
}

// PO Analysis response (JSON)
{
    pos_hit: [{"po": "PO1", "reason": "..."}],
    pos_not_hit: [{"po": "PO2", "reason": "..."}],
    po_word_hit: ["PO1", "PO3"],
    po_context_hit: ["PO5"],
    recommendations: ["Focus on PO2..."]
}
```

---

## 🎯 Summary

1. **Controllers** = Entry point, fetch database, call adapters
2. **Adapters** = Prepare data, call OpenAI services, format responses
3. **OpenAIService** = Makes actual API calls to OpenAI
4. **PromptBuilders** = Create prompts sent to OpenAI
5. **EvaluationService** = Calculates quality metrics (ROUGE/BERT)

**Data Flow**: Frontend → Controller → Adapter → OpenAI Service → OpenAI API → Response flows back

