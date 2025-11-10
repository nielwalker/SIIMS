# All OpenAI Related Code - Simple Explanation

## Controllers (HTTP Endpoints)

### SummaryController
**Location**: `app/Http/Controllers/Api/OpenAI/SummaryController.php`
- **What it is**: HTTP controller that handles OpenAI summary requests from chairperson role
- **Main function**: Receives HTTP requests from frontend, processes student activities and learnings, calls OpenAI to generate summaries, and returns JSON responses
- **Key methods**:
  - `test()` - Tests if OpenAI is configured and working
  - `summarize()` - Generates summaries using OpenAI based on activities, learnings, and assessment data
- **Routes**: 
  - `GET/POST /api/v1/summary/openai-summarize` - Generate summaries
  - `GET /api/v1/summary/openai-test` - Test OpenAI configuration

### CoordinatorSummaryController
**Location**: `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php`
- **What it is**: HTTP controller that handles OpenAI summary requests from coordinator role
- **Main function**: Receives HTTP requests from frontend, processes individual student data, calls OpenAI to generate summaries for single students, and can generate task suggestions for coordinators
- **Key methods**:
  - `summarize()` - Generates coordinator summaries using OpenAI with PO keyword detection
  - `handleWeeklyTasksRequest()` - Generates weekly task suggestions for coordinators based on gap POs
- **Routes**: 
  - `GET/POST /api/v1/summary/openai-summarize-coordinator` - Generate coordinator summaries

---

## Core Services

### OpenAIService
**Location**: `app/Services/OpenAI/OpenAIService.php`
- **What it is**: The main service that makes ALL actual API calls to OpenAI's API
- **Main function**: Handles HTTP requests to OpenAI API, manages API keys, normalizes messages, handles errors, and returns parsed responses
- **Key methods**:
  - `call()` - Makes the actual OpenAI API call with messages and options
  - `callSimple()` - Quick method for simple string prompts (backward compatibility)
  - `cleanText()` - Removes quotes and normalizes whitespace in text
  - `cleanDataArray()` - Cleans arrays of data by applying cleanText to each item
  - `enforceWeekPrefix()` - Ensures summaries start with proper week prefix like "For this week, those students "
  - `isAvailable()` - Checks if OpenAI API key is configured in environment
- **What it does**: This is the ONLY place that actually talks to OpenAI's API - all other code goes through this service

---

## Prompt Builders (Create Instructions for OpenAI)

### PromptBuilder
**Location**: `app/Services/OpenAI/PromptBuilder.php`
- **What it is**: Creates prompts (instructions) for chairperson summary generation
- **Main function**: Builds different types of prompts based on what type of summary is needed (overall, weekly, coordinator)
- **Key methods**:
  - `buildSummaryPrompt()` - Main method that creates prompts based on type
  - `buildOverallPrompt()` - Creates prompt for overall summary (all weeks combined)
  - `buildChairWeeklyPrompt()` - Creates prompt for weekly group summary
  - `buildCoordinatorWeeklyPrompt()` - Creates prompt for single student weekly summary
- **Types it handles**: overall_summary, chair_week, coordinator_week
- **What it does**: Takes activities, learnings, and assessment data, then creates formatted instructions for OpenAI to generate summaries

### CoordinatorPromptBuilder
**Location**: `app/Services/OpenAI/CoordinatorPromptBuilder.php`
- **What it is**: Creates prompts for coordinator summaries with PO keyword detection
- **Main function**: Scans student activities and learnings for PO-related keywords, then builds a prompt that includes this context for OpenAI
- **Key components**:
  - `PO_WORD_MAP` - Maps keywords to Program Outcomes (PO1-PO15), like "team" → PO8, "design" → PO5
  - `PO_DEFINITIONS` - Full definitions of all 15 Program Outcomes
- **Key methods**:
  - `buildPrompt()` - Main method that creates prompt with PO detection
  - `detectPOWordHits()` - Scans text for PO-related keywords and returns which POs were detected
- **What it does**: Detects which POs are mentioned in student work, then tells OpenAI to use this context when generating summaries

### ChairSummaryPromptBuilder
**Location**: `app/Services/OpenAI/ChairSummaryPromptBuilder.php`
- **What it is**: Creates comprehensive prompts for detailed PO analysis
- **Main function**: Builds very detailed prompts that include all 15 PO definitions, examples, context indicators, and instructions for OpenAI to analyze which POs students achieved
- **Key components**:
  - `PO_DETAILED` - Complete PO information with descriptions, examples, and context indicators for each PO1-PO15
- **Key methods**:
  - `buildPOAnalysisPrompt()` - Main method that creates comprehensive prompt
  - `buildSystemMessage()` - Creates the system prompt with PO identification instructions
  - `buildUserMessage()` - Creates the user prompt with actual student data
- **What it does**: Creates extremely detailed instructions for OpenAI to analyze student activities and identify which Program Outcomes were achieved, including rules like "orientation" = PO8, PO10, PO12, PO13

---

## Service Classes (Business Logic)

### ChairSummaryService
**Location**: `app/Services/OpenAI/ChairSummaryService.php`
- **What it is**: Orchestrates the complete process of generating summaries with PO analysis
- **Main function**: Coordinates prompt building, API calls, and response parsing to generate summaries with comprehensive Program Outcome analysis
- **Key methods**:
  - `generateSummaryWithPOAnalysis()` - Main method that does everything: builds prompt, calls OpenAI, extracts results
  - `normalizeSummary()` - Extracts summary text from OpenAI's JSON response
  - `extractPosArrays()` - Extracts pos_hit and pos_not_hit arrays from response
  - `extractPoHitTypes()` - Extracts po_word_hit and po_context_hit arrays
  - `extractRecommendations()` - Extracts recommendations array from response
  - `formatPosExplanation()` - Formats PO arrays into readable text
  - `getDefaultNotHitReason()` - Provides default reason when PO is not achieved
- **What it does**: Takes student text, activities, and learnings, then uses OpenAI to generate a summary AND analyze which of the 15 Program Outcomes were achieved, returning everything in a structured format

---

## Adapters (Bridge Between Old and New Code)

### ChairSummaryAdapter
**Location**: `app/Services/ChairSummaryAdapter.php`
- **What it is**: Adapter that connects the old system code to the new OpenAI services for chairperson summaries
- **Main function**: Receives requests from controllers, calls ChairSummaryService to do the actual OpenAI work, and returns formatted results back to controllers
- **Key methods**:
  - `summarize()` - Main method that receives data and calls ChairSummaryService
  - `normalizeSummary()` - Helper to extract summary from JSON responses
  - `extractPosArrays()` - Helper to extract PO arrays from responses
  - `formatPosExplanation()` - Formats PO data into readable text
- **What it does**: Acts as a bridge - controllers call this, and it calls the new ChairSummaryService instead of having OpenAI code directly in it
- **Before reorganization**: Had hundreds of lines of OpenAI API code directly inside
- **After reorganization**: Now just calls ChairSummaryService

### SummaryAdapter
**Location**: `app/Services/SummaryAdapter.php`
- **What it is**: Adapter for simple summary generation with keyword scoring
- **Main function**: Analyzes text and computes keyword scores (always), and optionally uses OpenAI for summary generation
- **Key methods**:
  - `analyze()` - Main method that does keyword analysis and optional OpenAI summary
- **What it does**: 
  - Always computes keyword scores for PO detection (no OpenAI needed)
  - If `$useGPT = true`, calls OpenAIService to generate summary
  - Returns both keyword scores and optional OpenAI summary
- **Before reorganization**: Had OpenAI API calls directly using Http::withToken()
- **After reorganization**: Now calls OpenAIService

---

## How They Work Together - Complete Flow

### Flow 1: Chairperson Wants a Simple Summary
```
1. Frontend → SummaryController::summarize() (receives HTTP request)
2. SummaryController → Extracts activities, learnings, assessment from request
3. SummaryController → PromptBuilder::buildSummaryPrompt() (creates prompt)
4. PromptBuilder → Returns formatted prompt with system and user messages
5. SummaryController → OpenAIService::call() (makes API call)
6. OpenAIService → Sends HTTP POST to OpenAI API
7. OpenAI API → Returns summary text
8. OpenAIService → Parses response and returns to SummaryController
9. SummaryController → Cleans and formats response
10. SummaryController → Returns JSON to frontend
```

### Flow 2: Chairperson Wants Summary with PO Analysis
```
1. Frontend → ChairSummaryController (receives HTTP request)
2. ChairSummaryController → ChairSummaryAdapter::summarize() (calls adapter)
3. ChairSummaryAdapter → ChairSummaryService::generateSummaryWithPOAnalysis() (calls service)
4. ChairSummaryService → ChairSummaryPromptBuilder::buildPOAnalysisPrompt() (creates detailed prompt)
5. ChairSummaryPromptBuilder → Returns comprehensive prompt with all PO definitions
6. ChairSummaryService → OpenAIService::call() (makes API call)
7. OpenAIService → Sends HTTP POST to OpenAI API
8. OpenAI API → Returns JSON with summary, pos_hit, pos_not_hit, recommendations
9. OpenAIService → Parses response and returns to ChairSummaryService
10. ChairSummaryService → Extracts summary, POs, recommendations from JSON
11. ChairSummaryService → Ensures all 15 POs are accounted for
12. ChairSummaryService → Returns complete result to ChairSummaryAdapter
13. ChairSummaryAdapter → Returns to ChairSummaryController
14. ChairSummaryController → Returns JSON to frontend
```

### Flow 3: Coordinator Wants a Summary
```
1. Frontend → CoordinatorSummaryController::summarize() (receives HTTP request)
2. CoordinatorSummaryController → Extracts activities, learnings, assessment
3. CoordinatorSummaryController → CoordinatorPromptBuilder::buildPrompt() (creates prompt)
4. CoordinatorPromptBuilder → detectPOWordHits() (scans for PO keywords)
5. CoordinatorPromptBuilder → Returns prompt with PO context
6. CoordinatorSummaryController → OpenAIService::callSimple() (makes API call)
7. OpenAIService → Sends HTTP POST to OpenAI API
8. OpenAI API → Returns summary text
9. OpenAIService → Parses response and returns to CoordinatorSummaryController
10. CoordinatorSummaryController → Ensures week prefix is correct
11. CoordinatorSummaryController → Returns JSON to frontend
```

---

## Quick Reference Table

| Class/File | What It Is | Main Function |
|------------|------------|---------------|
| **OpenAIService** | Core API service | Makes actual HTTP requests to OpenAI API |
| **SummaryController** | Chairperson controller | Handles HTTP requests for chairperson summaries |
| **CoordinatorSummaryController** | Coordinator controller | Handles HTTP requests for coordinator summaries |
| **PromptBuilder** | Simple prompt builder | Creates prompts for basic summaries |
| **CoordinatorPromptBuilder** | Coordinator prompt builder | Creates prompts with PO keyword detection |
| **ChairSummaryPromptBuilder** | Detailed prompt builder | Creates comprehensive prompts for PO analysis |
| **ChairSummaryService** | PO analysis orchestrator | Coordinates summary + PO analysis process |
| **ChairSummaryAdapter** | Chairperson bridge | Connects controllers to ChairSummaryService |
| **SummaryAdapter** | Simple summary bridge | Handles keyword scoring + optional OpenAI summaries |

---

## Key Concepts

### Program Outcomes (POs)
- **What**: 15 learning outcomes that BSIT students must achieve (PO1-PO15)
- **Examples**: 
  - PO1 = Apply knowledge of computing and mathematics
  - PO8 = Work effectively in teams
  - PO12 = Act with professional and ethical responsibilities
- **Purpose**: System analyzes student activities to determine which POs they achieved

### PO Detection Methods

#### Word-Based Detection (Keyword Matching)
- **How**: Scans text for specific keywords
- **Example**: Finding "team" or "collaborate" → PO8
- **Used By**: CoordinatorPromptBuilder, initial detection

#### Context-Based Detection (AI Analysis)
- **How**: OpenAI analyzes context and meaning
- **Example**: Understanding that "attended orientation" demonstrates teamwork
- **Used By**: ChairSummaryService for comprehensive analysis

### Hybrid Approach
- **Word-Based (40% weight)**: Fast keyword matching
- **Context-Based (60% weight)**: AI understanding of meaning
- **Final Score**: Combined weighted score

### Summary Types
- **Overall Summary**: Summary for all weeks combined
- **Weekly Summary**: Summary for a specific week
- **Coordinator Summary**: Summary for a single student
- **PO Analysis**: Detailed analysis of which POs were achieved

---

## File Locations

```
app/
├── Http/Controllers/Api/OpenAI/
│   ├── SummaryController.php              ← Chairperson summaries
│   └── CoordinatorSummaryController.php   ← Coordinator summaries
│
└── Services/OpenAI/
    ├── OpenAIService.php                  ← Core API service
    ├── PromptBuilder.php                  ← Simple prompt builder
    ├── CoordinatorPromptBuilder.php       ← Coordinator prompt builder
    ├── ChairSummaryPromptBuilder.php      ← Detailed PO prompt builder
    └── ChairSummaryService.php            ← PO analysis orchestrator

app/Services/
├── ChairSummaryAdapter.php                ← Chairperson bridge
└── SummaryAdapter.php                     ← Simple summary bridge
```

---

## Summary

**OpenAIService** = Makes ALL actual API calls to OpenAI - this is the only place that talks to OpenAI's API

**SummaryController** = Handles HTTP requests from chairperson for generating summaries

**CoordinatorSummaryController** = Handles HTTP requests from coordinator for generating summaries and task suggestions

**PromptBuilder** = Creates simple prompts for basic summary generation

**CoordinatorPromptBuilder** = Creates prompts with PO keyword detection for coordinator summaries

**ChairSummaryPromptBuilder** = Creates comprehensive prompts with all PO definitions for detailed PO analysis

**ChairSummaryService** = Orchestrates the complete process of generating summaries with PO analysis

**ChairSummaryAdapter** = Bridge that connects old code to new ChairSummaryService

**SummaryAdapter** = Bridge that handles keyword scoring and optional OpenAI summaries

All OpenAI code is now organized in `app/Services/OpenAI/` and `app/Http/Controllers/Api/OpenAI/` for easy navigation during defense!

