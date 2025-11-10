# Prompt Builder Comparison

## Overview

There are **4 different prompt builder classes** in the system, each serving a different purpose:

1. **`PromptBuilder.php`** - General summary prompts (for chairperson summaries)
2. **`ChairpersonPOPromptBuilder.php`** - PO analysis prompts for chairperson
3. **`CoordinatorPOPromptBuilder.php`** - PO analysis prompts for coordinator
4. **`CoordinatorPromptBuilder.php`** - Coordinator summary prompts (for single student summaries)

---

## 1. PromptBuilder.php

### Purpose
- **General summary generation** for chairperson views
- Handles multiple summary types (overall, weekly, coordinator)
- Returns **array format** for OpenAI API (system + user messages)

### Key Features
- ✅ Builds prompts for **summary generation only** (no PO analysis)
- ✅ Supports multiple types: `chair_overall`, `chair_week`, `coordinator_week`
- ✅ Returns structured array: `[['role' => 'system', 'content' => ...], ['role' => 'user', 'content' => ...]]`
- ✅ Focuses on **writing style** and **third-person conversion**

### Used By
- `ChairSummaryAdapter.php` - For generating chairperson summaries
- `OpenAI/ChairpersonOpenAISummaryController.php` - For chairperson summary generation

### Example Output Format
```php
[
    ['role' => 'system', 'content' => 'You are an academic writing expert...'],
    ['role' => 'user', 'content' => 'STUDENT ACTIVITIES: ...']
]
```

### Summary Prefixes
- **Overall**: "For overall, "
- **Chair Weekly**: "For this week, those students "
- **Coordinator Weekly**: "For this week, the student "

---

## 2. ChairpersonPOPromptBuilder.php

### Purpose
- **PO (Program Outcome) analysis** for **chairperson** views
- Analyzes student activities/learnings to identify achieved POs
- Returns **array format** for OpenAI API (system + user messages)

### Key Features
- ✅ Builds prompts for **PO analysis only** (not summary generation)
- ✅ Contains **detailed PO definitions** (PO1-PO15) with examples
- ✅ Includes **PO recognition rules** and **contextual interpretation**
- ✅ Returns structured JSON with:
  - `pos_hit` - Achieved POs
  - `pos_not_hit` - Not achieved POs
  - `po_word_hit` - POs found via keyword matching
  - `po_context_hit` - POs found via contextual analysis
  - `recommendations` - Suggestions for improvement
- ✅ **309 lines** - Most comprehensive prompt builder

### Used By
- `ChairpersonSummaryService.php` - For PO analysis in chairperson view
- `ChairSummaryAdapter.php` - For generating PO analysis results

### Example Output Format
```php
[
    ['role' => 'system', 'content' => 'You are a BSIT internship evaluator...'],
    ['role' => 'user', 'content' => '=== RAW WEEKLY REPORT DATA === ...']
]
```

### PO Analysis Focus
- Analyzes **raw database data** (activities + learnings)
- Identifies which POs are achieved based on activities
- Generates recommendations for unachieved POs
- Uses both **keyword matching** and **contextual analysis**

---

## 3. CoordinatorPOPromptBuilder.php

### Purpose
- **PO (Program Outcome) analysis** for **coordinator** views
- Analyzes student activities/learnings to identify achieved POs
- Returns **array format** for OpenAI API (system + user messages)

### Key Features
- ✅ Builds prompts for **PO analysis only** (not summary generation)
- ✅ Contains **detailed PO definitions** (PO1-PO15) with examples
- ✅ Includes **PO recognition rules** and **contextual interpretation**
- ✅ Returns structured JSON with:
  - `pos_hit` - Achieved POs
  - `pos_not_hit` - Not achieved POs
  - `po_word_hit` - POs found via keyword matching
  - `po_context_hit` - POs found via contextual analysis
  - `recommendations` - Suggestions for improvement
- ✅ **~300 lines** - Comprehensive prompt builder

### Used By
- `CoordinatorSummaryService.php` - For PO analysis in coordinator view
- `CoordinatorSummaryController.php` - For PO analysis in coordinator view (via `generateForStudent()`)

### Example Output Format
```php
[
    ['role' => 'system', 'content' => 'You are a BSIT internship evaluator...'],
    ['role' => 'user', 'content' => '=== RAW WEEKLY REPORT DATA === ...']
]
```

### PO Analysis Focus
- Analyzes **raw database data** (activities + learnings)
- Identifies which POs are achieved based on activities
- Generates recommendations for unachieved POs
- Uses both **keyword matching** and **contextual analysis**

---

## 4. CoordinatorPromptBuilder.php

### Purpose
- **Summary generation** for coordinator views (single student)
- Simpler, focused on single-student summaries
- Returns **string format** (not array)

### Key Features
- ✅ Builds prompts for **summary generation only** (no PO analysis)
- ✅ Includes **PO word mapping** for context (but doesn't analyze POs)
- ✅ Returns **simple string** (not array format)
- ✅ Detects PO keywords to provide context to OpenAI
- ✅ **120 lines** - Simplest prompt builder

### Used By
- `SummaryAdapter.php` - For generating coordinator summaries
- `OpenAI/CoordinatorSummaryController.php` - For coordinator summary generation

### Example Output Format
```php
"You are an academic writing expert. Write a polished, professional weekly summary...
Begin EXACTLY with: 'For this week, the student '...
SOURCE TEXT (cleaned): ...
PO WORD HITS (synonym match): ..."
```

### Summary Prefix
- **Coordinator**: "For this week, the student "

### PO Context (Not Analysis)
- Detects PO keywords in text for **context only**
- Does **NOT** perform PO analysis
- Keywords help OpenAI understand the content better
- Does **NOT** return PO analysis results

---

## Key Differences Summary

| Feature | PromptBuilder.php | ChairpersonPOPromptBuilder.php | CoordinatorPOPromptBuilder.php | CoordinatorPromptBuilder.php |
|---------|-------------------|--------------------------------|--------------------------------|------------------------------|
| **Purpose** | Summary generation | PO analysis | PO analysis | Summary generation |
| **Used For** | Chairperson summaries | Chairperson PO analysis | Coordinator PO analysis | Coordinator summaries |
| **Return Type** | Array (system + user) | Array (system + user) | Array (system + user) | String |
| **PO Analysis** | ❌ No | ✅ Yes (comprehensive) | ✅ Yes (comprehensive) | ❌ No (context only) |
| **PO Definitions** | ❌ No | ✅ Yes (detailed) | ✅ Yes (detailed) | ✅ Yes (simple) |
| **Summary Generation** | ✅ Yes | ❌ No | ❌ No | ✅ Yes |
| **File Size** | 181 lines | ~300 lines | ~300 lines | 120 lines |
| **Complexity** | Medium | High | High | Low |

---

## When Each Is Used

### PromptBuilder.php
```php
// In ChairSummaryAdapter.php
$prompt = $this->promptBuilder->buildSummaryPrompt(
    $activities, 
    $learnings, 
    '', 
    'overall_summary'
);
// Returns: Array for OpenAI API
// Purpose: Generate summary text
```

### ChairpersonPOPromptBuilder.php
```php
// In ChairpersonSummaryService.php (for chairperson)
$prompt = $this->promptBuilder->buildPOAnalysisPrompt(
    $text, 
    $week, 
    $activities, 
    $learnings
);
// Returns: Array for OpenAI API
// Purpose: Analyze POs and return JSON with pos_hit, pos_not_hit, etc.
```

### CoordinatorPOPromptBuilder.php
```php
// In CoordinatorSummaryService.php (for coordinator)
$prompt = $this->promptBuilder->buildPOAnalysisPrompt(
    $text, 
    $week, 
    $activities, 
    $learnings
);
// Returns: Array for OpenAI API
// Purpose: Analyze POs and return JSON with pos_hit, pos_not_hit, etc.
```

### CoordinatorPromptBuilder.php
```php
// In SummaryAdapter.php
$prompt = $this->coordinatorPromptBuilder->buildPrompt(
    $activities, 
    $learnings, 
    ''
);
// Returns: String prompt
// Purpose: Generate summary text for single student
```

---

## Why Three Different Builders?

### 1. **Separation of Concerns**
- **Summary generation** vs **PO analysis** are different tasks
- Each builder focuses on one specific purpose

### 2. **Different Output Formats**
- `PromptBuilder`, `ChairpersonPOPromptBuilder`, and `CoordinatorPOPromptBuilder` return arrays (for structured OpenAI API)
- `CoordinatorPromptBuilder` returns strings (for simpler API calls)

### 3. **Different Complexity Levels**
- Chairperson PO analysis needs detailed PO definitions and rules
- Coordinator summaries are simpler (single student, no PO analysis)
- General summaries are medium complexity

### 4. **Different Use Cases**
- **Chairperson**: Needs both summary AND PO analysis
- **Coordinator**: Needs only summary (PO analysis is separate)

---

## Code Flow Example

### Chairperson View (Summary + PO Analysis)

```
1. ChairSummaryAdapter.php
   ├─ Uses PromptBuilder.php → Generate summary
   └─ Uses ChairpersonSummaryService.php
      └─ Uses ChairpersonPOPromptBuilder.php → Analyze POs
```

### Coordinator View (Summary + PO Analysis)

```
1. SummaryAdapter.php
   └─ Uses CoordinatorPromptBuilder.php → Generate summary
   
2. CoordinatorSummaryController.php (generateForStudent method)
   └─ Uses CoordinatorSummaryService.php
      └─ Uses CoordinatorPOPromptBuilder.php → Analyze POs
```

---

## Summary

- **`PromptBuilder.php`**: General summary prompts for chairperson (array format)
- **`ChairpersonPOPromptBuilder.php`**: PO analysis prompts for chairperson (array format, comprehensive)
- **`CoordinatorPOPromptBuilder.php`**: PO analysis prompts for coordinator (array format, comprehensive)
- **`CoordinatorPromptBuilder.php`**: Summary prompts for coordinator (string format, simple)

Each serves a specific purpose and is used in different parts of the system!

