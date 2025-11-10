# Simple Explanation of All OpenAI Code

## Controllers (HTTP Endpoints)

### SummaryController
**Location**: `app/Http/Controllers/Api/OpenAI/SummaryController.php`
- **What it is**: HTTP controller that handles OpenAI summary requests from chairperson role
- **Main function**: Receives requests from frontend, processes data, calls OpenAI, and returns summaries
- **Endpoints**: 
  - `GET/POST /api/v1/summary/openai-summarize` - Generate summaries
  - `GET /api/v1/summary/openai-test` - Test if OpenAI is configured

### CoordinatorSummaryController
**Location**: `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php`
- **What it is**: HTTP controller that handles OpenAI summary requests from coordinator role
- **Main function**: Receives coordinator requests, generates summaries for individual students, and can generate task suggestions
- **Endpoints**: 
  - `GET/POST /api/v1/summary/openai-summarize-coordinator` - Generate coordinator summaries
  - Special feature: Can generate weekly task suggestions for coordinators based on gap POs

---

## Core Services

### OpenAIService
**Location**: `app/Services/OpenAI/OpenAIService.php`
- **What it is**: The main service that makes ALL actual API calls to OpenAI
- **Main function**: Handles HTTP requests to OpenAI API, manages API keys, normalizes messages, and returns responses
- **Key methods**:
  - `call()` - Makes the actual OpenAI API call
  - `callSimple()` - Quick method for simple prompts
  - `cleanText()` - Removes quotes and cleans text
  - `isAvailable()` - Checks if API key exists

---

## Prompt Builders (Create Instructions for OpenAI)

### PromptBuilder
**Location**: `app/Services/OpenAI/PromptBuilder.php`
- **What it is**: Creates prompts (instructions) for chairperson summary generation
- **Main function**: Builds different types of prompts based on what type of summary is needed (overall, weekly, etc.)
- **Types it handles**: overall_summary, chair_week, coordinator_week

### CoordinatorPromptBuilder
**Location**: `app/Services/OpenAI/CoordinatorPromptBuilder.php`
- **What it is**: Creates prompts for coordinator summaries with PO keyword detection
- **Main function**: Scans student activities/learnings for PO-related keywords, then builds a prompt that includes this context
- **Special feature**: Has a keyword map (PO_WORD_MAP) that detects which Program Outcomes are mentioned

### ChairSummaryPromptBuilder
**Location**: `app/Services/OpenAI/ChairSummaryPromptBuilder.php`
- **What it is**: Creates comprehensive prompts for detailed PO analysis
- **Main function**: Builds very detailed prompts that include all 15 PO definitions, examples, and instructions for OpenAI to analyze which POs students achieved
- **Special feature**: Contains complete PO definitions with examples and context indicators

---

## Service Classes (Business Logic)

### ChairSummaryService
**Location**: `app/Services/OpenAI/ChairSummaryService.php`
- **What it is**: Orchestrates the complete process of generating summaries with PO analysis
- **Main function**: Coordinates prompt building, API calls, and response parsing to generate summaries with Program Outcome analysis
- **What it does**:
  1. Builds comprehensive prompts using ChairSummaryPromptBuilder
  2. Calls OpenAI API using OpenAIService
  3. Extracts summary, PO hits, recommendations from response
  4. Ensures all 15 POs are accounted for
  5. Formats everything for the frontend

---

## Adapters (Bridge Between Old and New Code)

### ChairSummaryAdapter
**Location**: `app/Services/ChairSummaryAdapter.php`
- **What it is**: Adapter that connects the old system to the new OpenAI services
- **Main function**: Receives requests from controllers, calls ChairSummaryService, and returns formatted results
- **Note**: This file was updated to use the new ChairSummaryService instead of having OpenAI code directly in it

### SummaryAdapter
**Location**: `app/Services/SummaryAdapter.php`
- **What it is**: Adapter for simple summary generation
- **Main function**: Handles basic summary generation using OpenAIService
- **Note**: This file was updated to use the new OpenAIService instead of having OpenAI code directly in it

---

## How They Work Together - Simple Flow

### Example 1: Chairperson Wants a Summary
```
1. Frontend → SummaryController (receives request)
2. SummaryController → PromptBuilder (creates prompt)
3. SummaryController → OpenAIService (calls OpenAI API)
4. OpenAIService → OpenAI API (gets response)
5. OpenAIService → SummaryController (returns response)
6. SummaryController → Frontend (sends JSON)
```

### Example 2: Chairperson Wants Summary with PO Analysis
```
1. Frontend → ChairSummaryAdapter (receives request)
2. ChairSummaryAdapter → ChairSummaryService (processes request)
3. ChairSummaryService → ChairSummaryPromptBuilder (creates detailed prompt)
4. ChairSummaryService → OpenAIService (calls OpenAI API)
5. OpenAIService → OpenAI API (gets JSON response with POs)
6. ChairSummaryService → Extracts summary, POs, recommendations
7. ChairSummaryService → ChairSummaryAdapter (returns complete result)
8. ChairSummaryAdapter → Frontend (sends JSON)
```

### Example 3: Coordinator Wants a Summary
```
1. Frontend → CoordinatorSummaryController (receives request)
2. CoordinatorSummaryController → CoordinatorPromptBuilder (scans for PO keywords)
3. CoordinatorPromptBuilder → Creates prompt with PO context
4. CoordinatorSummaryController → OpenAIService (calls OpenAI API)
5. OpenAIService → OpenAI API (gets summary)
6. OpenAIService → CoordinatorSummaryController (returns response)
7. CoordinatorSummaryController → Frontend (sends JSON)
```

---

## Quick Reference

| File | What It Does |
|------|-------------|
| **OpenAIService** | Makes actual API calls to OpenAI |
| **SummaryController** | Handles chairperson summary requests |
| **CoordinatorSummaryController** | Handles coordinator summary requests |
| **PromptBuilder** | Creates prompts for simple summaries |
| **CoordinatorPromptBuilder** | Creates prompts with PO keyword detection |
| **ChairSummaryPromptBuilder** | Creates detailed prompts for PO analysis |
| **ChairSummaryService** | Orchestrates summary + PO analysis process |
| **ChairSummaryAdapter** | Bridge for chairperson summaries |
| **SummaryAdapter** | Bridge for simple summaries |

---

## Key Concepts

### Program Outcomes (POs)
- **What**: 15 learning outcomes that BSIT students must achieve (PO1-PO15)
- **Example**: PO8 = "Work effectively in teams", PO12 = "Act with professional and ethical responsibilities"
- **Purpose**: System analyzes student activities to see which POs they achieved

### PO Detection
- **Word-Based**: Scans text for keywords (e.g., "team" → PO8)
- **Context-Based**: AI understands meaning (e.g., "attended orientation" → PO8, PO10, PO12, PO13)

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
```

