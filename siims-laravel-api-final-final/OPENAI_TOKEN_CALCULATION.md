# OpenAI Token Calculation - Detailed Analysis

## Token Estimation Methodology

**Approximate Token Count**: ~4 characters = 1 token (for English text)
- Short words: 1 token each
- Long words: 2-3 tokens
- Punctuation: Usually part of word tokens
- System/role markers: ~3-5 tokens per message

---

## 1. PromptBuilder (Simple Summaries)

### 1.1 Overall Summary (`chair_overall` / `overall_summary`)

**System Message:**
```
"You are an academic writing expert. Create a polished, professional summary for an internship program report."
```
- **Tokens**: ~20 tokens

**User Message:**
```
STUDENT ACTIVITIES: {activitiesText}
LEARNING OUTCOMES: {learningsText}
ASSESSMENT: {assessment}
WRITING REQUIREMENTS: [10 requirements + style guidelines]
```
- **Base prompt text**: ~350 tokens
- **Activities text** (variable): ~50-200 tokens (depends on data)
- **Learnings text** (variable): ~50-200 tokens (depends on data)
- **Assessment text** (variable): ~50-150 tokens (depends on data)

**Total Input Tokens:**
- **Minimum**: 20 + 350 + 150 = **~520 tokens**
- **Average**: 20 + 350 + 300 = **~670 tokens**
- **Maximum**: 20 + 350 + 550 = **~920 tokens**

---

### 1.2 Chairperson Weekly Summary (`chair_week`)

**System Message:**
```
"You are an academic writing expert. Create a polished, professional weekly summary for an internship program report."
```
- **Tokens**: ~25 tokens

**User Message:**
```
STUDENT ACTIVITIES: {activitiesText}
LEARNING OUTCOMES: {learningsText}
ASSESSMENT: {assessment}
WRITING REQUIREMENTS: [8 requirements + style guidelines]
```
- **Base prompt text**: ~300 tokens
- **Activities text** (variable): ~50-200 tokens
- **Learnings text** (variable): ~50-200 tokens
- **Assessment text** (variable): ~50-150 tokens

**Total Input Tokens:**
- **Minimum**: 25 + 300 + 150 = **~475 tokens**
- **Average**: 25 + 300 + 300 = **~625 tokens**
- **Maximum**: 25 + 300 + 550 = **~875 tokens**

---

### 1.3 Coordinator Weekly Summary (`coordinator_week`)

**System Message:**
```
"You are an academic writing expert. Create a polished, professional weekly summary for an internship program report."
```
- **Tokens**: ~25 tokens

**User Message:**
```
STUDENT ACTIVITIES: {activitiesText}
LEARNING OUTCOMES: {learningsText}
ASSESSMENT: {assessment}
WRITING REQUIREMENTS: [8 requirements + style guidelines]
```
- **Base prompt text**: ~300 tokens
- **Activities text** (variable): ~30-150 tokens (single student, typically shorter)
- **Learnings text** (variable): ~30-150 tokens
- **Assessment text** (variable): ~30-100 tokens

**Total Input Tokens:**
- **Minimum**: 25 + 300 + 90 = **~415 tokens**
- **Average**: 25 + 300 + 180 = **~505 tokens**
- **Maximum**: 25 + 300 + 400 = **~725 tokens**

---

## 2. CoordinatorPromptBuilder (Coordinator Summaries with PO Analysis)

### 2.1 With PO Word Hits Detected

**Prompt Structure:**
```
Intro (~30 tokens)
Requirements (~50 tokens)
Source Text: {activities + learnings} (~100-300 tokens)
PO Word Hits Section: "PO1 => words: apply, compute..." (~200-400 tokens)
Task instruction (~30 tokens)
```

**Total Input Tokens:**
- **Minimum**: 30 + 50 + 100 + 200 + 30 = **~410 tokens**
- **Average**: 30 + 50 + 200 + 300 + 30 = **~610 tokens**
- **Maximum**: 30 + 50 + 300 + 400 + 30 = **~810 tokens**

---

### 2.2 Without PO Word Hits (Includes All PO Definitions)

**Prompt Structure:**
```
Intro (~30 tokens)
Requirements (~50 tokens)
Source Text: {activities + learnings} (~100-300 tokens)
PO Definitions Section: All 15 PO definitions (~800-1000 tokens)
Task instruction (~30 tokens)
```

**Total Input Tokens:**
- **Minimum**: 30 + 50 + 100 + 800 + 30 = **~1,010 tokens**
- **Average**: 30 + 50 + 200 + 900 + 30 = **~1,210 tokens**
- **Maximum**: 30 + 50 + 300 + 1000 + 30 = **~1,410 tokens**

**Note**: This is a single string prompt (not array), so no system/user role overhead.

---

## 3. ChairSummaryPromptBuilder (PO Analysis - Most Complex)

### 3.1 System Message

**Content Breakdown:**
- Base introduction: ~30 tokens
- PO Context Guide (15 POs × ~150 tokens each): ~2,250 tokens
  - Each PO includes: description (~30 tokens) + examples (~50 tokens) + context indicators (~70 tokens)
- Mandatory PO identification process: ~200 tokens
- PO analysis source instructions: ~50 tokens
- Summary generation instructions: ~30 tokens
- Tasks list: ~300 tokens
- PO recognition rules: ~150 tokens
- Contextual interpretation methodology: ~200 tokens
- Common activity mappings: ~100 tokens
- Critical enforcement: ~150 tokens
- JSON response requirements: ~200 tokens
- Output format example: ~250 tokens
- Final validation checklist: ~150 tokens

**System Message Total:**
- **Estimated**: **~4,010 tokens**

---

### 3.2 User Message

**Content Breakdown:**
- "IGNORE THIS FOR PO ANALYSIS" section: ~50 tokens
- Summary generation data (first 500 chars): ~125 tokens
- "USE THIS FOR PO ANALYSIS" header: ~20 tokens
- Activities/Tasks section: ~100-400 tokens (variable)
- Learnings section: ~100-400 tokens (variable)
- Critical instructions: ~150 tokens

**User Message Total:**
- **Minimum**: 50 + 125 + 20 + 200 + 150 = **~545 tokens**
- **Average**: 50 + 125 + 20 + 400 + 150 = **~745 tokens**
- **Maximum**: 50 + 125 + 20 + 800 + 150 = **~1,145 tokens**

---

### 3.3 Total Input Tokens for ChairSummaryPromptBuilder

**Total (System + User):**
- **Minimum**: 4,010 + 545 = **~4,555 tokens**
- **Average**: 4,010 + 745 = **~4,755 tokens**
- **Maximum**: 4,010 + 1,145 = **~5,155 tokens**

---

## Summary Table

| Service/Builder | Type | Min Input Tokens | Avg Input Tokens | Max Input Tokens | Max Output Tokens |
|----------------|------|------------------|------------------|------------------|-------------------|
| **PromptBuilder** | Overall Summary | 520 | 670 | 920 | 3000 |
| **PromptBuilder** | Chair Weekly | 475 | 625 | 875 | 3000 |
| **PromptBuilder** | Coordinator Weekly | 415 | 505 | 725 | 3000 |
| **CoordinatorPromptBuilder** | With PO Hits | 410 | 610 | 810 | 300 |
| **CoordinatorPromptBuilder** | Without PO Hits | 1,010 | 1,210 | 1,410 | 300 |
| **ChairSummaryPromptBuilder** | PO Analysis | 4,555 | 4,755 | 5,155 | 3000 |

---

## Estimated Output Tokens

### PromptBuilder Outputs
- **Overall Summary**: ~150-400 tokens (longer paragraph)
- **Weekly Summaries**: ~100-300 tokens (2-3 sentences)

### CoordinatorPromptBuilder Output
- **Coordinator Summary**: ~50-200 tokens (brief summary)

### ChairSummaryPromptBuilder Output
- **Summary**: ~100-200 tokens
- **JSON with PO analysis**: ~500-1,500 tokens
  - corrected_activities: ~50-200 tokens
  - corrected_learnings: ~50-200 tokens
  - summary: ~100-200 tokens
  - pos_hit array: ~200-600 tokens (depends on number of POs)
  - pos_not_hit array: ~200-400 tokens
  - po_word_hit: ~20-50 tokens
  - po_context_hit: ~20-50 tokens
  - recommendations: ~100-300 tokens

---

## Total Token Usage Per Request

| Service | Input Tokens | Output Tokens | Total Tokens |
|---------|--------------|---------------|--------------|
| **PromptBuilder (Overall)** | 520-920 | 150-400 | **670-1,320** |
| **PromptBuilder (Weekly)** | 415-875 | 100-300 | **515-1,175** |
| **CoordinatorPromptBuilder** | 410-1,410 | 50-200 | **460-1,610** |
| **ChairSummaryPromptBuilder** | 4,555-5,155 | 600-1,700 | **5,155-6,855** |

---

## Cost Estimation (GPT-4o-mini Pricing)

### Pricing (as of 2024)
- **Input**: $0.15 per 1M tokens
- **Output**: $0.60 per 1M tokens

### Per Request Cost

1. **PromptBuilder (Average)**
   - Input: 600 tokens × $0.15/1M = $0.00009
   - Output: 200 tokens × $0.60/1M = $0.00012
   - **Total: ~$0.00021 per request**

2. **CoordinatorPromptBuilder (Average)**
   - Input: 900 tokens × $0.15/1M = $0.00014
   - Output: 125 tokens × $0.60/1M = $0.00008
   - **Total: ~$0.00022 per request**

3. **ChairSummaryPromptBuilder (Average)**
   - Input: 4,755 tokens × $0.15/1M = $0.00071
   - Output: 1,150 tokens × $0.60/1M = $0.00069
   - **Total: ~$0.00140 per request**

---

## Monthly Cost Estimates (Example Usage)

### Scenario 1: Light Usage
- 100 PromptBuilder requests/month
- 50 CoordinatorPromptBuilder requests/month
- 20 ChairSummaryPromptBuilder requests/month

**Cost**: (100 × $0.00021) + (50 × $0.00022) + (20 × $0.00140) = **$0.057/month**

### Scenario 2: Moderate Usage
- 500 PromptBuilder requests/month
- 200 CoordinatorPromptBuilder requests/month
- 100 ChairSummaryPromptBuilder requests/month

**Cost**: (500 × $0.00021) + (200 × $0.00022) + (100 × $0.00140) = **$0.289/month**

### Scenario 3: Heavy Usage
- 2,000 PromptBuilder requests/month
- 1,000 CoordinatorPromptBuilder requests/month
- 500 ChairSummaryPromptBuilder requests/month

**Cost**: (2,000 × $0.00021) + (1,000 × $0.00022) + (500 × $0.00140) = **$1.12/month**

---

## Optimization Recommendations

1. **ChairSummaryPromptBuilder is the most expensive** (~4,755 tokens input)
   - Consider caching PO definitions if possible
   - Could potentially reduce system message size by ~500-1,000 tokens

2. **CoordinatorPromptBuilder without hits** is 2x more expensive than with hits
   - The PO definitions section adds ~800-1,000 tokens
   - Consider optimizing this section

3. **Monitor actual usage** using the token tracking now implemented
   - Check logs for real token counts
   - Adjust estimates based on actual data

4. **Cache results** when possible to avoid redundant API calls

---

## Notes

- Token counts are estimates based on character count and typical English text
- Actual token counts may vary based on:
  - Specific vocabulary used
  - Length of activities/learnings data
  - OpenAI's tokenization algorithm
- The code now tracks actual token usage - check logs for real numbers
- These estimates assume typical data sizes; very long activities/learnings will increase token counts

