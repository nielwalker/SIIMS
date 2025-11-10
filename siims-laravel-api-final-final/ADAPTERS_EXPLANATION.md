# Adapters Explanation - How They Use OpenAI

## What Are Adapters?

**Adapters** = Bridge classes that connect the old system code to the new organized OpenAI services. They allow the existing controllers to work without major changes while using the new centralized OpenAI code.

---

## ChairSummaryAdapter

**Location**: `app/Services/ChairSummaryAdapter.php`

**What it is**: Adapter that handles chairperson summary generation with comprehensive PO analysis

**Main Function**: 
- Receives requests from controllers (like `ChairSummaryController`)
- Calls the new `ChairSummaryService` to do the actual OpenAI work
- Returns formatted results back to controllers

**How it uses OpenAI**:
```php
// OLD WAY (before reorganization):
// Had OpenAI API calls directly inside this file (hundreds of lines of OpenAI code)

// NEW WAY (after reorganization):
if ($useGPT && !empty($clean)) {
    $chairSummaryService = app(ChairSummaryService::class);
    $result = $chairSummaryService->generateSummaryWithPOAnalysis($clean, $week, $activities, $learnings);
    return $result;
}
```

**What it does**:
1. Receives text, week, activities, and learnings from controller
2. Checks if GPT should be used (`$useGPT` parameter)
3. If yes, calls `ChairSummaryService` (which handles all OpenAI logic)
4. Returns the complete result with summary, PO hits, recommendations, etc.

**Key Methods**:
- `summarize()` - Main method that orchestrates the process
- `normalizeSummary()` - Helper to extract summary from JSON responses
- `extractPosArrays()` - Helper to extract PO arrays from responses
- `formatPosExplanation()` - Formats PO data into readable text

**Why it exists**: 
- Keeps backward compatibility with existing controllers
- Allows gradual migration from old code to new organized structure
- Provides a clean interface for controllers to use

---

## SummaryAdapter

**Location**: `app/Services/SummaryAdapter.php`

**What it is**: Adapter for simple summary generation with keyword scoring

**Main Function**:
- Analyzes text and generates summaries
- Computes keyword scores for PO detection (without OpenAI)
- Optionally uses OpenAI for summary generation

**How it uses OpenAI**:
```php
// OLD WAY (before reorganization):
// Had OpenAI API calls directly using Http::withToken()

// NEW WAY (after reorganization):
if ($useGPT && $clean) {
    $openAIService = app(OpenAIService::class);
    
    // Build prompt based on analysis type
    $messages = [
        ['role' => 'system', 'content' => $sys],
        ['role' => 'user', 'content' => $usr]
    ];
    
    $response = $openAIService->call($messages, [
        'model' => $model,
        'max_tokens' => 200,
        'temperature' => 0.6,
        'timeout' => 30,
    ]);
}
```

**What it does**:
1. Analyzes text and computes keyword scores (always done, no OpenAI needed)
2. If `$useGPT` is true, calls `OpenAIService` to generate summary
3. Returns both keyword scores and optional OpenAI summary

**Key Methods**:
- `analyze()` - Main method that does keyword analysis and optional OpenAI summary
- Keyword scoring - Computes PO scores based on keyword matching (no OpenAI)

**Special Features**:
- **Always computes keyword scores** - This happens regardless of OpenAI
- **Optional OpenAI summary** - Only uses OpenAI if `$useGPT = true`
- **Different prompts** - Uses different prompts for 'coordinator', 'chairman', or generic analysis

**Why it exists**:
- Provides keyword-based PO scoring (fast, no API calls)
- Optionally enhances with OpenAI summaries
- Used by various parts of the system for quick analysis

---

## How Adapters Connect to OpenAI Services

### ChairSummaryAdapter Flow:
```
Controller → ChairSummaryAdapter::summarize()
           ↓
    ChairSummaryService::generateSummaryWithPOAnalysis()
           ↓
    ChairSummaryPromptBuilder::buildPOAnalysisPrompt()
           ↓
    OpenAIService::call()
           ↓
    OpenAI API
```

### SummaryAdapter Flow:
```
Controller → SummaryAdapter::analyze()
           ↓
    (Computes keyword scores - no OpenAI)
           ↓
    If $useGPT = true:
           ↓
    OpenAIService::call()
           ↓
    OpenAI API
```

---

## Key Differences

| Feature | ChairSummaryAdapter | SummaryAdapter |
|---------|---------------------|----------------|
| **Purpose** | Comprehensive PO analysis | Simple summary + keyword scores |
| **OpenAI Usage** | Always uses OpenAI (if enabled) | Optional OpenAI (if $useGPT = true) |
| **PO Analysis** | Full PO analysis with recommendations | Keyword-based scoring only |
| **Complexity** | High - detailed PO analysis | Low - simple summaries |
| **Service Used** | ChairSummaryService | OpenAIService (directly) |
| **Response** | Summary + PO hits + recommendations | Summary + keyword scores |

---

## Why We Have Adapters

1. **Backward Compatibility**: Existing controllers don't need to change
2. **Gradual Migration**: Can migrate code piece by piece
3. **Clean Interface**: Controllers just call `adapter->method()`, don't need to know about OpenAI services
4. **Separation of Concerns**: Adapters handle data formatting, services handle OpenAI logic

---

## Summary

**ChairSummaryAdapter** = Bridge for chairperson summaries with full PO analysis. Calls `ChairSummaryService` which handles all OpenAI work.

**SummaryAdapter** = Bridge for simple summaries. Computes keyword scores always, optionally calls `OpenAIService` for summaries.

Both adapters were **updated** to use the new organized OpenAI services instead of having OpenAI code directly in them.

