# Complete Explanation of All OpenAI Code and Functions

## Overview
This document explains all OpenAI-related code in the SIIMS system, including their purposes, functions, and how they work together.

---

## 1. OpenAIService.php - Core OpenAI API Service

**Location**: `app/Services/OpenAI/OpenAIService.php`

**Purpose**: This is the **centralized service** that handles ALL OpenAI API calls. It eliminates duplicate code and provides a single point of interaction with OpenAI.

### Key Functions:

#### 1.1 `call($messages, $options = [])` - Main API Call Method
- **Purpose**: Makes the actual HTTP request to OpenAI's API
- **Parameters**:
  - `$messages`: Can be a string (simple prompt) or array (structured messages with system/user roles)
  - `$options`: Configuration like model, max_tokens, temperature, timeout
- **Returns**: Array with `success`, `content`, `error`, and `raw` response
- **How it works**:
  1. Gets API key from environment
  2. Normalizes messages to OpenAI format
  3. Sends POST request to `https://api.openai.com/v1/chat/completions`
  4. Returns parsed response or error

#### 1.2 `callSimple($prompt, $model, $maxTokens, $temperature, $timeout)` - Quick Call Method
- **Purpose**: Simplified method for backward compatibility
- **Parameters**: Simple string prompt and basic configuration
- **Returns**: Array with `success`, `summary`, and `error`
- **Use Case**: Used by coordinator summaries for simpler prompts

#### 1.3 `cleanText($text)` - Text Cleaning Utility
- **Purpose**: Removes quotes and normalizes whitespace
- **Function**: Cleans text before sending to OpenAI or after receiving response
- **Example**: `"Hello  world"` → `"Hello world"`

#### 1.4 `cleanDataArray($data)` - Array Cleaning Utility
- **Purpose**: Applies `cleanText()` to each item in an array
- **Use Case**: Cleans activities and learnings arrays before processing

#### 1.5 `enforceWeekPrefix($text, $prefix)` - Week Prefix Enforcement
- **Purpose**: Ensures summaries start with proper week prefix
- **Example**: Adds "For this week, those students " if not present
- **Use Case**: Maintains consistent formatting for weekly summaries

#### 1.6 `isAvailable()` - Availability Check
- **Purpose**: Checks if OpenAI API key is configured
- **Returns**: Boolean indicating if OpenAI can be used
- **Use Case**: Prevents API calls when key is missing

---

## 2. PromptBuilder.php - Chairperson Summary Prompts

**Location**: `app/Services/OpenAI/PromptBuilder.php`

**Purpose**: Builds prompts for chairperson summary generation (without PO analysis).

### Key Functions:

#### 2.1 `buildSummaryPrompt($activities, $learnings, $assessment, $type)` - Main Builder
- **Purpose**: Creates prompts based on summary type
- **Types Supported**:
  - `overall_summary` or `chair_overall`: Overall summary for all weeks
  - `chair_week`: Weekly summary for group of students
  - `coordinator_week`: Weekly summary for single student
- **Returns**: Array with system and user messages for OpenAI

#### 2.2 `buildOverallPrompt()` - Overall Summary Prompt
- **Purpose**: Creates prompt for overall summary
- **Key Features**:
  - Starts with "For overall, "
  - Third-person writing (students, they, their)
  - Academic writing style
  - Single polished paragraph

#### 2.3 `buildChairWeeklyPrompt()` - Chairperson Weekly Prompt
- **Purpose**: Creates prompt for weekly group summary
- **Key Features**:
  - Starts with "For this week, those students "
  - Third-person writing
  - 2-3 coherent sentences
  - Synthesizes activities and learnings

#### 2.4 `buildCoordinatorWeeklyPrompt()` - Coordinator Weekly Prompt
- **Purpose**: Creates prompt for single student weekly summary
- **Key Features**:
  - Starts with "For this week, the student "
  - Third-person writing
  - Focuses on individual student

---

## 3. CoordinatorPromptBuilder.php - Coordinator Summary with PO Detection

**Location**: `app/Services/OpenAI/CoordinatorPromptBuilder.php`

**Purpose**: Builds prompts for coordinator summaries with Program Outcome (PO) keyword detection.

### Key Components:

#### 3.1 `PO_WORD_MAP` - Keyword Mapping
- **Purpose**: Maps keywords to Program Outcomes (PO1-PO15)
- **Example**:
  - `PO1`: ['apply', 'compute', 'calculate', 'solve']
  - `PO8`: ['team', 'collaborate', 'assist', 'coordinate']
- **Use Case**: Detects which POs are mentioned in student activities/learnings

#### 3.2 `PO_DEFINITIONS` - PO Definitions
- **Purpose**: Full definitions of all 15 Program Outcomes
- **Use Case**: Provides context to OpenAI when no keywords are detected

#### 3.3 `buildPrompt($activities, $learnings, $assessment)` - Main Builder
- **Purpose**: Creates prompt with PO detection
- **Process**:
  1. Combines activities and learnings into text
  2. Detects PO keywords using `detectPOWordHits()`
  3. If keywords found: Includes PO word hits in prompt
  4. If no keywords: Includes full PO definitions for context
- **Returns**: Complete prompt string

#### 3.4 `detectPOWordHits($lowerText)` - Keyword Detection
- **Purpose**: Scans text for PO-related keywords
- **Returns**: Array of detected POs with matched keywords
- **Example Output**:
  ```php
  [
    'PO5' => ['count' => 2, 'matched' => ['design', 'develop']],
    'PO8' => ['count' => 1, 'matched' => ['team']]
  ]
  ```

---

## 4. ChairSummaryPromptBuilder.php - Comprehensive PO Analysis Prompts

**Location**: `app/Services/OpenAI/ChairSummaryPromptBuilder.php`

**Purpose**: Builds comprehensive prompts for chairperson summaries with detailed PO analysis.

### Key Components:

#### 4.1 `PO_DETAILED` - Detailed PO Information
- **Purpose**: Contains full PO descriptions, examples, and context indicators
- **Structure**: For each PO (PO1-PO15):
  - `description`: Official PO definition
  - `examples`: Practical examples of activities
  - `context_indicators`: Keywords and what to look for

#### 4.2 `buildPOAnalysisPrompt($text, $week, $activities, $learnings)` - Main Builder
- **Purpose**: Creates comprehensive prompt for PO analysis
- **Process**:
  1. Builds PO context guide from `PO_DETAILED`
  2. Creates system message with:
     - PO identification process
     - PO recognition rules
     - JSON response requirements
     - Validation checklist
  3. Creates user message with:
     - Raw weekly report data (activities/learnings)
     - Instructions for PO analysis
- **Returns**: Array with system and user messages

#### 4.3 `buildSystemMessage()` - System Message Builder
- **Purpose**: Creates the system prompt that instructs OpenAI
- **Key Instructions**:
  - How to identify POs from activities/learnings
  - Rules for PO recognition (e.g., "orientation" = PO8, PO10, PO12, PO13)
  - JSON format requirements
  - Validation checklist

#### 4.4 `buildUserMessage()` - User Message Builder
- **Purpose**: Creates the user prompt with actual data
- **Structure**:
  - Summary generation data (for summary only)
  - Raw weekly report data (for PO analysis - MANDATORY)
  - Critical instructions for PO identification

---

## 5. ChairSummaryService.php - Chairperson Summary with PO Analysis

**Location**: `app/Services/OpenAI/ChairSummaryService.php`

**Purpose**: Orchestrates the entire process of generating chairperson summaries with PO analysis.

### Key Functions:

#### 5.1 `generateSummaryWithPOAnalysis($text, $week, $activities, $learnings)` - Main Method
- **Purpose**: Complete workflow for summary + PO analysis
- **Process**:
  1. Checks if OpenAI is available
  2. Builds prompt using `ChairSummaryPromptBuilder`
  3. Calls OpenAI API using `OpenAIService`
  4. Extracts summary, PO hits, recommendations from response
  5. Ensures all 15 POs are accounted for
  6. Returns complete result array

#### 5.2 `normalizeSummary($content)` - Summary Extraction
- **Purpose**: Extracts summary from OpenAI's JSON response
- **Process**: Parses JSON and extracts "summary for this section on a week" field

#### 5.3 `extractPosArrays($content)` - PO Extraction
- **Purpose**: Extracts `pos_hit` and `pos_not_hit` arrays from response
- **Returns**: Array with 'hit' and 'notHit' keys

#### 5.4 `extractPoHitTypes($content)` - PO Type Extraction
- **Purpose**: Extracts `po_word_hit` and `po_context_hit` arrays
- **Returns**: Array with 'word' and 'context' keys
- **Difference**:
  - `po_word_hit`: POs detected by keyword matching
  - `po_context_hit`: POs detected by contextual analysis

#### 5.5 `extractRecommendations($content)` - Recommendations Extraction
- **Purpose**: Extracts recommendations array from response
- **Returns**: Array of recommendation strings

#### 5.6 `formatPosExplanation($title, $items)` - Format PO Explanations
- **Purpose**: Formats PO arrays into readable text
- **Example**: `"PO5 – Students participated in orientation demonstrating teamwork"`

#### 5.7 `getDefaultNotHitReason($po)` - Default Reasons
- **Purpose**: Provides default reason when PO is not achieved
- **Use Case**: When OpenAI doesn't provide a reason for not-hit POs

---

## 6. SummaryController.php - Chairperson OpenAI Controller

**Location**: `app/Http/Controllers/Api/OpenAI/SummaryController.php`

**Purpose**: HTTP controller that handles OpenAI summary requests from chairperson role.

### Key Functions:

#### 6.1 `test()` - Test Endpoint
- **Purpose**: Verifies OpenAI configuration
- **Returns**: JSON with status, OpenAI configuration status, user info
- **Route**: `GET /api/v1/summary/openai-test`

#### 6.2 `summarize($request)` - Summary Generation
- **Purpose**: Main endpoint for generating summaries
- **Process**:
  1. Receives request (POST or GET)
  2. Extracts activities, learnings, assessment from request
  3. Cleans data using `OpenAIService::cleanDataArray()` and `cleanText()`
  4. Builds prompt using `PromptBuilder::buildSummaryPrompt()`
  5. Calls OpenAI using `OpenAIService::call()`
  6. Cleans and formats response
  7. Enforces week prefix if needed
  8. Returns JSON response
- **Routes**: 
  - `POST /api/v1/summary/openai-summarize`
  - `GET /api/v1/summary/openai-summarize`
- **Error Handling**: Returns 503 with `openai_unavailable: true` if OpenAI fails

---

## 7. CoordinatorSummaryController.php - Coordinator OpenAI Controller

**Location**: `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php`

**Purpose**: HTTP controller that handles OpenAI summary requests from coordinator role.

### Key Functions:

#### 7.1 `summarize($request)` - Summary Generation
- **Purpose**: Main endpoint for coordinator summaries
- **Process**:
  1. Receives request data
  2. Handles special case: `chair_weekly_tasks_json` type (for task suggestions)
  3. Extracts and cleans activities, learnings, assessment
  4. Builds prompt using `CoordinatorPromptBuilder::buildPrompt()`
  5. Calls OpenAI using `OpenAIService::callSimple()`
  6. Ensures summary starts with "For this week, the student "
  7. Returns JSON response
- **Routes**:
  - `POST /api/v1/summary/openai-summarize-coordinator`
  - `GET /api/v1/summary/openai-summarize-coordinator`

#### 7.2 `handleWeeklyTasksRequest($coordinators)` - Weekly Tasks Generation
- **Purpose**: Generates task suggestions for coordinators based on gap POs
- **Process**:
  1. Formats coordinator data with gaps (POs with zero)
  2. Creates prompt asking for task suggestions
  3. Calls OpenAI
  4. Parses JSON response with `tasksPerCoordinator`
  5. Returns structured task suggestions
- **Use Case**: When chairperson wants AI-generated task suggestions for coordinators

---

## 8. How They Work Together - Complete Flow

### Flow 1: Chairperson Summary Generation (Simple)

```
1. Frontend → SummaryController::summarize()
2. SummaryController → PromptBuilder::buildSummaryPrompt()
3. PromptBuilder → Returns formatted prompt
4. SummaryController → OpenAIService::call()
5. OpenAIService → Makes HTTP request to OpenAI API
6. OpenAI → Returns summary text
7. OpenAIService → Returns parsed response
8. SummaryController → Cleans and formats response
9. SummaryController → Returns JSON to frontend
```

### Flow 2: Chairperson Summary with PO Analysis

```
1. Frontend → ChairSummaryAdapter::summarize()
2. ChairSummaryAdapter → ChairSummaryService::generateSummaryWithPOAnalysis()
3. ChairSummaryService → ChairSummaryPromptBuilder::buildPOAnalysisPrompt()
4. ChairSummaryPromptBuilder → Returns comprehensive prompt with PO definitions
5. ChairSummaryService → OpenAIService::call()
6. OpenAIService → Makes HTTP request to OpenAI API
7. OpenAI → Returns JSON with summary, pos_hit, pos_not_hit, recommendations
8. OpenAIService → Returns parsed response
9. ChairSummaryService → Extracts summary, POs, recommendations
10. ChairSummaryService → Ensures all 15 POs are accounted for
11. ChairSummaryService → Returns complete result
12. ChairSummaryAdapter → Returns to controller
13. Controller → Returns JSON to frontend
```

### Flow 3: Coordinator Summary Generation

```
1. Frontend → CoordinatorSummaryController::summarize()
2. CoordinatorSummaryController → CoordinatorPromptBuilder::buildPrompt()
3. CoordinatorPromptBuilder → detectPOWordHits() (scans for keywords)
4. CoordinatorPromptBuilder → Returns prompt with PO context
5. CoordinatorSummaryController → OpenAIService::callSimple()
6. OpenAIService → Makes HTTP request to OpenAI API
7. OpenAI → Returns summary text
8. OpenAIService → Returns parsed response
9. CoordinatorSummaryController → Ensures week prefix
10. CoordinatorSummaryController → Returns JSON to frontend
```

---

## 9. Key Concepts Explained

### 9.1 Program Outcomes (POs)
- **What**: 15 learning outcomes that BSIT students must achieve
- **Examples**: 
  - PO1: Apply knowledge of computing and mathematics
  - PO8: Work effectively in teams
  - PO12: Act with professional and ethical responsibilities
- **Purpose**: System analyzes student activities to determine which POs are achieved

### 9.2 PO Detection Methods

#### Word-Based Detection (Keyword Matching)
- **How**: Scans text for specific keywords
- **Example**: Finding "team" or "collaborate" → PO8
- **Used By**: `CoordinatorPromptBuilder`, initial detection in `ChairSummaryPromptBuilder`

#### Context-Based Detection (AI Analysis)
- **How**: OpenAI analyzes context and meaning
- **Example**: Understanding that "attended orientation" demonstrates teamwork
- **Used By**: `ChairSummaryService` for comprehensive analysis

### 9.3 Hybrid Approach
- **Word-Based (40% weight)**: Fast keyword matching
- **Context-Based (60% weight)**: AI understanding of meaning
- **Final Score**: Combined weighted score

### 9.4 Prompt Engineering
- **System Message**: Defines OpenAI's role and instructions
- **User Message**: Contains actual data to analyze
- **Purpose**: Guides OpenAI to produce desired output format

### 9.5 Error Handling
- **No API Key**: Returns error immediately, no API call
- **API Failure**: Returns 503 status with `openai_unavailable: true`
- **No Fallbacks**: System doesn't generate fake data when OpenAI fails

---

## 10. Configuration

### Environment Variables
```env
OPENAI_API_KEY=your_api_key_here
```

### Default Settings
- **Model**: `gpt-4o-mini` (for most operations)
- **Model (Coordinator)**: `gpt-3.5-turbo` (for simpler summaries)
- **Max Tokens**: 3000 (for PO analysis), 200-500 (for simple summaries)
- **Temperature**: 0.2 (for consistency), 0.6-0.7 (for creativity)
- **Timeout**: 90 seconds (for PO analysis), 30 seconds (for simple summaries)

---

## 11. Response Formats

### Simple Summary Response
```json
{
  "summary": "For this week, those students participated in...",
  "success": true
}
```

### PO Analysis Response
```json
{
  "summary": "For this week, those students...",
  "usedGPT": true,
  "pos_hit": [
    {"po": "PO5", "reason": "Students participated in orientation demonstrating teamwork"},
    {"po": "PO8", "reason": "Students engaged in discussions showing communication skills"}
  ],
  "pos_not_hit": [
    {"po": "PO1", "reason": "No evidence of mathematical knowledge application"}
  ],
  "po_word_hit": ["PO5", "PO8"],
  "po_context_hit": ["PO5", "PO8", "PO10", "PO13"],
  "recommendations": [
    "Students should engage in collaborative programming projects...",
    "Encourage students to participate in code review sessions..."
  ]
}
```

### Error Response
```json
{
  "error": "OpenAI is not available right now",
  "message": "API key not configured",
  "openai_unavailable": true
}
```

---

## 12. Best Practices

1. **Always check availability** before making API calls
2. **Clean data** before sending to OpenAI
3. **Use appropriate prompts** for different use cases
4. **Handle errors gracefully** - return proper status codes
5. **Log errors** for debugging
6. **No hard-coded fallbacks** - be transparent about OpenAI unavailability
7. **Validate responses** - ensure all required fields are present
8. **Account for all POs** - ensure PO1-PO15 are all in either hit or not_hit

---

## Summary

All OpenAI code is now organized in:
- **Services**: `app/Services/OpenAI/` - Business logic
- **Controllers**: `app/Http/Controllers/Api/OpenAI/` - HTTP endpoints
- **Single Point of API Calls**: `OpenAIService` handles all OpenAI API interactions
- **Specialized Builders**: Different prompt builders for different use cases
- **Comprehensive Analysis**: `ChairSummaryService` handles complex PO analysis

This organization makes it easy to:
- Understand the flow
- Debug issues
- Modify prompts
- Add new features
- Explain during defense

