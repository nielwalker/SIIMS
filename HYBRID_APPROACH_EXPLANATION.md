# Hybrid Approach for Program Outcome (PO) Graph - Complete Explanation

## Overview
The hybrid approach combines **keyword-based matching** (text mining) with **OpenAI contextual analysis** (AI/ML) to calculate PO achievement scores for the bar graph visualization.

---

## Step-by-Step Process

### Step 1: Collect Weekly Report Data
**Input**: All weekly reports from students under a coordinator/section for a specific week

**Example Data**:
```
Activities: 
- "Attended orientation meeting"
- "Used AutoCAD software"
- "Discussed project requirements"

Learnings:
- "Learned about printer types"
- "Understood VIMS system"
```

---

### Step 2: Keyword-Based Matching (40% Weight)

#### 2.1 Define Keyword Sets
Each PO (PO1-PO15) has a predefined set of keywords:

```javascript
keywordSets = [
  PO1: ["math", "mathematics", "science", "algorithm", "compute", "analysis"],
  PO2: ["best practice", "standard", "policy", "method", "procedure"],
  PO3: ["analyze", "analysis", "problem", "root cause", "diagnose"],
  PO4: ["user need", "requirement", "stakeholder", "ux", "usability"],
  PO5: ["design", "implement", "evaluate", "build", "develop", "test"],
  // ... and so on for PO6-PO15
]
```

#### 2.2 Count Keyword Matches
For each PO, count how many keywords from its set appear in the combined text:

```javascript
// Combined text from all weekly reports (lowercased)
const combinedText = "attended orientation meeting used autocad software discussed project requirements learned about printer types understood vims system";

// Count matches for each PO
counts = [
  PO1: 0,  // No math/algorithm keywords found
  PO2: 0,  // No best practice keywords found
  PO3: 1,  // Found "discussed" (related to analyze)
  PO4: 2,  // Found "requirement", "project"
  PO5: 3,  // Found "attended", "meeting", "discussed"
  // ... etc
]
```

#### 2.3 Calculate Keyword Score (Percentage)
```javascript
totalKeywordCount = sum of all counts = 0 + 0 + 1 + 2 + 3 + ... = 10

keywordScore[i] = (counts[i] / totalKeywordCount) × 100

Example:
keywordScore[0] = (0 / 10) × 100 = 0%    // PO1
keywordScore[1] = (0 / 10) × 100 = 0%    // PO2
keywordScore[2] = (1 / 10) × 100 = 10%   // PO3
keywordScore[3] = (2 / 10) × 100 = 20%   // PO4
keywordScore[4] = (3 / 10) × 100 = 30%   // PO5
// ... etc
```

**Result**: `keywordScore = [0, 0, 10, 20, 30, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]`

---

### Step 3: OpenAI Contextual Analysis (60% Weight)

#### 3.1 Send Data to OpenAI
The backend sends activities and learnings to OpenAI with detailed PO descriptions and instructions.

#### 3.2 OpenAI Returns Analysis
OpenAI analyzes the data and returns:
- `pos_hit`: Array of POs that were achieved (with reasons)
- `poContextHit`: Array of PO codes achieved through contextual analysis
- `poWordHit`: Array of PO codes achieved through keyword matching

**Example OpenAI Response**:
```json
{
  "pos_hit": [
    {"po": "PO4", "reason": "Students used AutoCAD software during training"},
    {"po": "PO5", "reason": "Students participated in orientation activities"},
    {"po": "PO6", "reason": "Students engaged in discussions"}
  ],
  "poContextHit": ["PO5", "PO6", "PO13"],
  "poWordHit": ["PO4", "PO6"]
}
```

#### 3.3 Build AI Scores Array
```javascript
// Combine all OpenAI PO data
allAIPOs = Set(["PO4", "PO5", "PO6", "PO13"])

// Create AI scores array (0 or 1 for each PO)
aiScores = Array.from({ length: 15 }, (_, i) => {
  const code = `PO${i + 1}`;
  return allAIPOs.has(code) ? 1 : 0;
});

// Result: aiScores = [0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0]
//                      PO1 PO2 PO3 PO4 PO5 PO6 PO7 PO8 PO9 PO10 PO11 PO12 PO13 PO14 PO15
```

**Result**: `aiScores = [0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0]`

---

### Step 4: Hybrid Formula (Weighted Combination)

#### 4.1 Define Weights
```javascript
const alpha = 0.4;  // 40% weight for keyword matching
const beta = 0.6;   // 60% weight for AI analysis
```

#### 4.2 Calculate Hybrid Score (Only for Confirmed POs)
**Important**: Only calculate scores for POs that OpenAI confirmed in `pos_hit` (source of truth).

```javascript
// Get confirmed POs from pos_hit
confirmedPOs = Set(["PO4", "PO5", "PO6"])

// For each PO (PO1-PO15):
for (let i = 0; i < 15; i++) {
  const poCode = `PO${i + 1}`;
  const k = keywordScore[i];      // Keyword score (0-100%)
  const a = aiScores[i];           // AI score (0 or 1)
  
  if (confirmedPOs.has(poCode)) {
    // PO is confirmed by OpenAI - calculate hybrid score
    hybridScore = (alpha × k) + (beta × a × 100)
                 = (0.4 × k) + (0.6 × a × 100)
    
    // Ensure minimum 50% for visible bars
    finalScore[i] = Math.max(50, Math.round(hybridScore))
  } else {
    // PO not confirmed - set to 0
    finalScore[i] = 0
  }
}
```

#### 4.3 Example Calculations

**For PO4** (confirmed by OpenAI):
```
k = 20%  (from keyword matching)
a = 1    (OpenAI confirmed it)

hybridScore = (0.4 × 20) + (0.6 × 1 × 100)
            = 8 + 60
            = 68%

finalScore[3] = Math.max(50, 68) = 68%
```

**For PO5** (confirmed by OpenAI):
```
k = 30%  (from keyword matching)
a = 1    (OpenAI confirmed it)

hybridScore = (0.4 × 30) + (0.6 × 1 × 100)
            = 12 + 60
            = 72%

finalScore[4] = Math.max(50, 72) = 72%
```

**For PO6** (confirmed by OpenAI):
```
k = 10%  (from keyword matching)
a = 1    (OpenAI confirmed it)

hybridScore = (0.4 × 10) + (0.6 × 1 × 100)
            = 4 + 60
            = 64%

finalScore[5] = Math.max(50, 64) = 64%
```

**For PO13** (NOT in pos_hit, but in poContextHit):
```
Since PO13 is NOT in confirmedPOs (pos_hit), it gets score = 0
```

**For PO1, PO2, PO3, PO7-PO15** (not confirmed):
```
All get score = 0 (not shown in graph)
```

#### 4.4 Final Scores Array
```javascript
finalScores = [
  0,   // PO1: 0% (not confirmed)
  0,   // PO2: 0% (not confirmed)
  0,   // PO3: 0% (not confirmed)
  68,  // PO4: 68% (confirmed, hybrid score)
  72,  // PO5: 72% (confirmed, hybrid score)
  64,  // PO6: 64% (confirmed, hybrid score)
  0,   // PO7: 0% (not confirmed)
  0,   // PO8: 0% (not confirmed)
  0,   // PO9: 0% (not confirmed)
  0,   // PO10: 0% (not confirmed)
  0,   // PO11: 0% (not confirmed)
  0,   // PO12: 0% (not confirmed)
  0,   // PO13: 0% (not in pos_hit, so 0)
  0,   // PO14: 0% (not confirmed)
  0    // PO15: 0% (not confirmed)
]
```

---

## Complete Example with Real Numbers

### Input Data:
- **Activities**: "Attended orientation", "Used AutoCAD", "Discussed requirements"
- **Learnings**: "Learned about printers", "Understood VIMS"

### Step-by-Step Calculation:

#### 1. Keyword Matching:
```
Text: "attended orientation used autocad discussed requirements learned printers understood vims"

Keyword counts:
PO1: 0 matches → 0%
PO2: 0 matches → 0%
PO3: 1 match ("discussed") → 10%
PO4: 2 matches ("requirement", "autocad") → 20%
PO5: 3 matches ("attended", "orientation", "discussed") → 30%
PO6: 1 match ("discussed") → 10%
PO13: 1 match ("learned") → 10%
Others: 0 matches → 0%

keywordScore = [0, 0, 10, 20, 30, 10, 0, 0, 0, 0, 0, 0, 10, 0, 0]
```

#### 2. OpenAI Analysis:
```
pos_hit = [
  {"po": "PO4", "reason": "Used AutoCAD software"},
  {"po": "PO5", "reason": "Participated in orientation"},
  {"po": "PO6", "reason": "Engaged in discussions"}
]

aiScores = [0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0]
```

#### 3. Hybrid Calculation:
```
Confirmed POs: ["PO4", "PO5", "PO6"]

PO4: (0.4 × 20) + (0.6 × 1 × 100) = 8 + 60 = 68%
PO5: (0.4 × 30) + (0.6 × 1 × 100) = 12 + 60 = 72%
PO6: (0.4 × 10) + (0.6 × 1 × 100) = 4 + 60 = 64%

All others: 0% (not confirmed)
```

#### 4. Final Graph Scores:
```
finalScores = [0, 0, 0, 68, 72, 64, 0, 0, 0, 0, 0, 0, 0, 0, 0]
```

---

## Key Points

### 1. **Source of Truth**: OpenAI's `pos_hit`
- Only POs in `pos_hit` get scores > 0
- Graph matches "Program Outcomes Achieved" section exactly

### 2. **Hybrid Formula**:
```
finalScore = (0.4 × keywordScore) + (0.6 × aiScore × 100)
```

### 3. **Why Hybrid?**
- **Keyword matching**: Fast, objective, but can miss context
- **AI analysis**: Understands context, but can be inconsistent
- **Combined**: Best of both worlds - objective keyword evidence + contextual AI understanding

### 4. **Minimum Score**: 50%
- If a PO is confirmed by OpenAI, it gets at least 50% to ensure visible bars
- This prevents confirmed POs from having very low scores due to weak keyword matches

### 5. **Weights**:
- **40% keyword** (alpha = 0.4): Text mining component
- **60% AI** (beta = 0.6): Contextual analysis component
- These weights can be adjusted if needed

---

## Mathematical Formula Summary

For each PO (PO1-PO15):

```
IF PO is in pos_hit (OpenAI confirmed):
  keywordScore = (keywordCount / totalKeywordCount) × 100
  aiScore = 1 (if OpenAI confirmed) or 0 (if not)
  
  hybridScore = (0.4 × keywordScore) + (0.6 × aiScore × 100)
  finalScore = max(50, round(hybridScore))
  
ELSE:
  finalScore = 0
```

---

## Why This Approach Works

1. **Accuracy**: OpenAI's contextual analysis ensures correct PO identification
2. **Consistency**: Keyword matching provides objective evidence
3. **Visualization**: Hybrid scores create meaningful bar heights in the graph
4. **Alignment**: Graph scores match the "Program Outcomes Achieved" text section
5. **Flexibility**: Weights can be adjusted to emphasize keyword or AI analysis

---

## Edge Cases Handled

1. **No OpenAI data**: Falls back to keyword-only scoring
2. **Empty pos_hit**: All scores = 0 (no achievements)
3. **Low keyword matches**: Minimum 50% ensures visible bars for confirmed POs
4. **Missing POs**: Only confirmed POs get scores, others = 0

---

## Code Location

- **Frontend**: `siims-react-app/src/components/chairperson/ChairpersonSummary.jsx`
  - Function: `computeScores()` (lines ~150-350)
  
- **Backend**: `siims-laravel-api-final-final/app/Services/ChairSummaryAdapter.php`
  - OpenAI prompt and PO extraction logic

