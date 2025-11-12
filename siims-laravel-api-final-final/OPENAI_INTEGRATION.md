# OpenAI Integration Documentation

## How It Works

OpenAI integration generates summaries and performs Program Outcome (PO) analysis from student weekly reports. The system uses GPT-4o-mini model to process activities and learnings.

## Flow

### Coordinator Flow

```
Frontend Request
    ↓ // Sends POST request with studentId, week, useGPT
CoordinatorSummaryController.php
    ↓ // Fetches weekly entries from database, combines activities and learnings
CoordinatorSummaryAdapter.php
    ↓ // Builds summary prompt with student data
CoordinatorSummaryPromptBuilder.php (builds prompt)
    ↓ // Sends prompt to OpenAI API
OpenAIService.php → OpenAI API
    ↓ // Receives summary text, generates PO analysis
CoordinatorSummaryService.php (PO analysis)
    ↓ // Builds PO analysis prompt with PO definitions
CoordinatorPOPromptBuilder.php (builds PO prompt)
    ↓ // Sends PO prompt to OpenAI API
OpenAIService.php → OpenAI API
    ↓ // Returns summary and PO analysis
Response to Frontend
```

### Chairperson Flow

```
Frontend Request
    ↓ // Sends GET request with coordinatorId, sectionId, week, useGPT
ChairpersonSummaryController.php
    ↓ // Fetches weekly entries for all students, checks cache
ChairSummaryAdapter.php
    ↓ // Builds summary prompt with aggregated student data
ChairSummaryPromptBuilder.php (builds summary prompt)
    ↓ // Sends prompt to OpenAI API
OpenAIService.php → OpenAI API
    ↓ // Receives summary text, generates PO analysis
ChairpersonSummaryService.php (PO analysis)
    ↓ // Builds PO analysis prompt with PO definitions
ChairpersonPOPromptBuilder.php (builds PO prompt)
    ↓ // Sends PO prompt to OpenAI API
OpenAIService.php → OpenAI API
    ↓ // Returns summary and PO analysis
Response to Frontend
```

## File Names

### Core Service
- `app/Services/OpenAI/OpenAIService.php` - Main OpenAI API service

### Controllers
- `app/Http/Controllers/Api/Coordinator/CoordinatorSummaryController.php` - Coordinator summary endpoint
- `app/Http/Controllers/Api/Chairperson/ChairpersonSummaryController.php` - Chairperson summary endpoint
- `app/Http/Controllers/Api/OpenAI/CoordinatorOpenAISummaryController.php` - Direct OpenAI coordinator endpoint
- `app/Http/Controllers/Api/OpenAI/ChairpersonOpenAISummaryController.php` - Direct OpenAI chairperson endpoint

### Coordinator Services
- `app/Services/Coordinator/CoordinatorSummaryAdapter.php` - Coordinator summary adapter
- `app/Services/Coordinator/CoordinatorSummaryService.php` - Coordinator PO analysis service
- `app/Services/Coordinator/CoordinatorSummaryPromptBuilder.php` - Coordinator summary prompt builder
- `app/Services/Coordinator/CoordinatorPOPromptBuilder.php` - Coordinator PO analysis prompt builder

### Chairperson Services
- `app/Services/Chairperson/ChairSummaryAdapter.php` - Chairperson summary adapter
- `app/Services/Chairperson/ChairpersonSummaryService.php` - Chairperson PO analysis service
- `app/Services/Chairperson/ChairSummaryPromptBuilder.php` - Chairperson summary prompt builder
- `app/Services/Chairperson/ChairpersonPOPromptBuilder.php` - Chairperson PO analysis prompt builder

### Evaluation
- `app/Services/OpenAI/SummaryEvaluationService.php` - ROUGE/BERT score evaluation

### Traits
- `app/Services/OpenAI/Traits/TextProcessingTrait.php` - Shared text processing functions

## Logic / Functions

### OpenAIService.php

**Function: `call($messages, $options)`**
- Normalizes messages to OpenAI format
- Makes HTTP POST to `https://api.openai.com/v1/chat/completions`
- Model: `gpt-4o-mini`
- Default max_tokens: 4000
- Default temperature: 0.2
- Logs token usage (prompt_tokens, completion_tokens, total_tokens)
- Returns: `{success, content, error, raw, usage}`

**Function: `callSimple($prompt, $model, $maxTokens, $temperature, $timeout)`**
- Wrapper for simple prompt calls
- Returns: `{success, summary, error, usage}`

**Function: `cleanText($text)`**
- Removes HTML tags, quotes, normalizes whitespace

**Function: `enforceWeekPrefix($text, $prefix)`**
- Ensures summary starts with correct week prefix
- Removes duplicate prefixes

### CoordinatorSummaryController.php

**Function: `generate($request)`**
- Receives: studentId, week, useGPT, analysisType
- Fetches weekly entries from database
- Combines activities and learnings
- Calls CoordinatorSummaryAdapter
- Calls SummaryEvaluationService for metrics
- Returns: summary, poAnalysis, evaluation

### CoordinatorSummaryAdapter.php

**Function: `analyze($text, $week, $useGPT)`**
- Builds prompt using CoordinatorSummaryPromptBuilder
- Calls OpenAIService.callSimple()
- Cleans and formats response
- Returns: summary, keywordScores, usedGPT

### CoordinatorSummaryService.php

**Function: `generateSummaryWithPOAnalysis($text, $week, $activities, $learnings)`**
- Builds PO prompt using CoordinatorPOPromptBuilder
- Calls OpenAIService.call()
- Parses JSON response
- Extracts: pos_hit, pos_not_hit, po_word_hit, po_context_hit, recommendations
- Ensures all not-met POs have recommendations
- Returns: PO analysis array

### CoordinatorSummaryPromptBuilder.php

**Function: `buildSummaryPrompt($text, $week)`**
- Constructs prompt with:
  - System message (instructions)
  - Week context
  - Student activities and learnings
  - Format requirements (third-person)
- Returns: formatted prompt string

### CoordinatorPOPromptBuilder.php

**Function: `buildPOAnalysisPrompt($text, $week, $activities, $learnings)`**
- Constructs prompt with:
  - PO definitions (PO1-PO15)
  - Activities and learnings
  - Instructions to identify achieved/not-met POs
  - JSON format requirements
  - Recommendation requirements (one per not-met PO)
- Returns: formatted prompt string

### ChairpersonSummaryController.php

**Function: `generate($request)`**
- Receives: coordinatorId, sectionId, week, useGPT
- Fetches weekly entries for all students
- Checks cache (po_analysis_cache table)
- Calls ChairSummaryAdapter
- Calls SummaryEvaluationService for metrics
- Returns: summary, poAnalysis, evaluation

### ChairSummaryAdapter.php

**Function: `summarize($combined, $week, $useGPT, $activities, $learnings)`**
- Builds prompt using ChairSummaryPromptBuilder
- Calls OpenAIService.call()
- Cleans and formats response
- Returns: summary text

### ChairpersonSummaryService.php

**Function: `generateSummaryWithPOAnalysis($text, $week, $activities, $learnings)`**
- Builds PO prompt using ChairpersonPOPromptBuilder
- Calls OpenAIService.call()
- Parses JSON response
- Extracts: pos_hit, pos_not_hit, po_word_hit, po_context_hit, recommendations
- Ensures all not-met POs have recommendations
- Returns: PO analysis array

### ChairSummaryPromptBuilder.php

**Function: `buildSummaryPrompt($text, $week)`**
- Constructs prompt with:
  - System message (instructions)
  - Week context (overall or specific week)
  - Aggregated activities and learnings
  - Format requirements (third-person, plural)
- Returns: formatted prompt string

### ChairpersonPOPromptBuilder.php

**Function: `buildPOAnalysisPrompt($text, $week, $activities, $learnings)`**
- Constructs prompt with:
  - PO definitions (PO1-PO15)
  - Aggregated activities and learnings
  - Instructions to identify achieved/not-met POs across all students
  - JSON format requirements
  - Recommendation requirements (one per not-met PO)
- Returns: formatted prompt string

### SummaryEvaluationService.php

**Function: `evaluate($candidateSummary, $referenceText)`**
- Calculates ROUGE-1, ROUGE-2, ROUGE-L scores
- Calculates BERT Score
- Compares generated summary vs raw database data
- Returns: evaluation metrics (precision, recall, F1)

## Data Processing

1. **Input**: Weekly entries (activities, learnings) from database
2. **Text Combination**: Activities and learnings combined into single text
3. **Prompt Building**: Prompt builders construct OpenAI prompts
4. **API Call**: OpenAIService sends request to OpenAI API
5. **Response Parsing**: JSON response parsed for summary/PO analysis
6. **Text Cleaning**: HTML tags, quotes removed, whitespace normalized
7. **Output**: Formatted summary and PO analysis returned to frontend

## Token Usage

- Average input tokens: 2,303
- Average output tokens: 326
- Average total tokens: 2,629
- Logged in: `storage/logs/laravel.log` as "OpenAI Token Usage"

## Configuration

- Model: `gpt-4o-mini`
- Max Tokens: 4000 (default), 2000-3000 (varies by endpoint)
- Temperature: 0.2
- Timeout: 60 seconds
- API Key: `OPENAI_API_KEY` environment variable

