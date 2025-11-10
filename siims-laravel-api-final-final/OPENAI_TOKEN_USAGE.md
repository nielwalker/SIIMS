# OpenAI Token Usage - Current Configuration

## Important Note

**`max_tokens` is the OUTPUT limit**, not the input. It specifies the maximum number of tokens OpenAI can generate in the response.

**Actual tokens sent (input)** = The size of your prompt (system + user messages)
**Actual tokens received (output)** = The size of OpenAI's response (up to max_tokens limit)

---

## Current Token Configuration

### ⚠️ Important Note
**`max_tokens` is the OUTPUT limit**, not the input. It specifies the maximum number of tokens OpenAI can generate in the response.

**Actual tokens sent (input)** = The size of your prompt (system + user messages)  
**Actual tokens received (output)** = The size of OpenAI's response (up to max_tokens limit)

### Token Tracking
**✅ NOW IMPLEMENTED**: The code now extracts and logs token usage from OpenAI responses. Check logs for actual token counts.

### 1. ChairSummaryService (PO Analysis)
**Location**: `app/Services/OpenAI/ChairSummaryService.php`
- **max_tokens**: **3000** (maximum tokens for response)
- **Model**: `gpt-4o-mini`
- **Use Case**: Comprehensive PO analysis with detailed prompts
- **Estimated Input Tokens**: ~2000-4000 tokens (very large prompt with all PO definitions)
- **Estimated Output Tokens**: 500-2000 tokens (summary + JSON with PO analysis)
- **Actual Usage**: Check logs - token usage is now tracked automatically

### 2. SummaryController (Chairperson Summaries)
**Location**: `app/Http/Controllers/Api/OpenAI/SummaryController.php`
- **max_tokens**: **3000** (maximum tokens for response)
- **Model**: `gpt-4o-mini`
- **Use Case**: Simple summary generation
- **Estimated Input Tokens**: ~500-1000 tokens (activities, learnings, assessment)
- **Estimated Output Tokens**: 100-500 tokens (summary text)

### 3. CoordinatorSummaryController (Coordinator Summaries)
**Location**: `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php`
- **max_tokens**: **300** (maximum tokens for response)
- **Model**: `gpt-3.5-turbo`
- **Use Case**: Short summaries for individual students
- **Estimated Input Tokens**: ~300-600 tokens (activities, learnings, PO context)
- **Estimated Output Tokens**: 50-200 tokens (brief summary)

### 4. SummaryAdapter (Simple Analysis)
**Location**: `app/Services/SummaryAdapter.php`
- **max_tokens**: **200** (maximum tokens for response)
- **Model**: `gpt-4o-mini`
- **Use Case**: Quick summaries
- **Estimated Input Tokens**: ~200-400 tokens
- **Estimated Output Tokens**: 50-150 tokens

---

## Token Limits Summary

| Service/Controller | max_tokens (Output Limit) | Model | Estimated Input Tokens | Estimated Output Tokens |
|-------------------|---------------------------|-------|------------------------|-------------------------|
| **ChairSummaryService** | 3000 | gpt-4o-mini | 2000-4000 | 500-2000 |
| **SummaryController** | 3000 | gpt-4o-mini | 500-1000 | 100-500 |
| **CoordinatorSummaryController** | 300 | gpt-3.5-turbo | 300-600 | 50-200 |
| **SummaryAdapter** | 200 | gpt-4o-mini | 200-400 | 50-150 |

---

## How to Track Actual Token Usage

Currently, the code does **NOT track actual token usage**. To track tokens, you need to:

### Option 1: Extract from OpenAI Response

OpenAI API returns token usage in the response:
```json
{
  "usage": {
    "prompt_tokens": 1234,
    "completion_tokens": 567,
    "total_tokens": 1801
  }
}
```

### Option 2: Add Token Tracking to OpenAIService

Modify `OpenAIService::call()` to extract and return token usage:

```php
if ($response->successful()) {
    $data = $response->json();
    $content = $data['choices'][0]['message']['content'] ?? null;
    $usage = $data['usage'] ?? null; // Token usage info
    
    return [
        'success' => true,
        'content' => $content ? trim($content) : null,
        'error' => null,
        'raw' => $data,
        'usage' => $usage, // Add this
    ];
}
```

### Option 3: Use OpenAI's Token Counter

Install a token counting library or use OpenAI's tiktoken library to count tokens before sending.

---

## Estimated Total Token Usage Per Request

**📊 For detailed token calculations, see `OPENAI_TOKEN_CALCULATION.md`**

### Chairperson PO Analysis (Most Expensive) - ChairSummaryPromptBuilder
- **Input**: ~4,555-5,155 tokens (system message: ~4,010 tokens + user message: ~545-1,145 tokens)
- **Output**: ~600-1,700 tokens (summary + JSON with PO analysis)
- **Total**: ~5,155-6,855 tokens per request

### Chairperson Simple Summary - PromptBuilder
- **Overall Summary**: ~520-920 input tokens, ~150-400 output tokens = **~670-1,320 total**
- **Weekly Summary**: ~415-875 input tokens, ~100-300 output tokens = **~515-1,175 total**

### Coordinator Summary - CoordinatorPromptBuilder
- **With PO Hits**: ~410-810 input tokens, ~50-200 output tokens = **~460-1,010 total**
- **Without PO Hits**: ~1,010-1,410 input tokens, ~50-200 output tokens = **~1,060-1,610 total**

---

## Cost Estimation (Approximate)

**📊 For detailed cost calculations, see `OPENAI_TOKEN_CALCULATION.md`**

### GPT-4o-mini Pricing (as of 2024)
- **Input**: ~$0.15 per 1M tokens
- **Output**: ~$0.60 per 1M tokens

### Per Request Cost (Estimated - Average Usage)

1. **Chairperson PO Analysis** (ChairSummaryPromptBuilder): 
   - Input: 4,755 tokens × $0.15/1M = $0.00071
   - Output: 1,150 tokens × $0.60/1M = $0.00069
   - **Total: ~$0.00140 per request**

2. **Chairperson Simple Summary** (PromptBuilder):
   - Input: 600 tokens × $0.15/1M = $0.00009
   - Output: 200 tokens × $0.60/1M = $0.00012
   - **Total: ~$0.00021 per request**

3. **Coordinator Summary** (CoordinatorPromptBuilder):
   - Input: 900 tokens × $0.15/1M = $0.00014
   - Output: 125 tokens × $0.60/1M = $0.00008
   - **Total: ~$0.00022 per request**

---

## Recommendations

1. **Add Token Tracking**: Modify OpenAIService to extract and log token usage from responses
2. **Monitor Usage**: Track token usage over time to understand costs
3. **Optimize Prompts**: Reduce prompt size where possible (especially ChairSummaryPromptBuilder)
4. **Cache Results**: Cache summaries when possible to avoid redundant API calls
5. **Set Budget Limits**: Configure OpenAI API with usage limits

---

## Current Code Locations

- **Token Limits Set In**:
  - `OpenAIService.php` - DEFAULT_MAX_TOKENS = 3000
  - `ChairSummaryService.php` - max_tokens = 3000
  - `SummaryController.php` - max_tokens = 3000
  - `CoordinatorSummaryController.php` - max_tokens = 300
  - `SummaryAdapter.php` - max_tokens = 200

- **No Token Tracking Currently**: The code does not extract or log actual token usage from OpenAI responses.

