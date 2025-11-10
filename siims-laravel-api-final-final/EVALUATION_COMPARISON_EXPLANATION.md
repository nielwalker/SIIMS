# ROUGE and BERT Score Comparison Explanation

## What Are ROUGE and BERT Scores Comparing?

The ROUGE and BERT scores compare **OpenAI-generated summaries** against **raw database data** (the original student activities and learnings).

---

## Comparison Structure

### 1. **Generated Summary (What OpenAI Created)**
- **Source**: OpenAI API response (GPT-3.5-turbo or GPT-4o-mini)
- **Content**: AI-generated summary text
- **Example**: 
  ```
  "For this week, the student worked on implementing the user authentication system. 
  They learned about JWT tokens and password hashing. The student also created 
  database migrations for user management."
  ```

### 2. **Reference Text (What We Compare Against)**
- **Source**: Raw data from `weekly_entries` table in database
- **Content**: Original student activities + learnings (unprocessed)
- **Format**: 
  ```
  "Activities: [original tasks text] Learnings: [original learnings text]"
  ```
- **Example**:
  ```
  "Activities: I worked on implementing user authentication system. I created 
  login and registration forms. I set up JWT token generation. Learnings: I 
  learned about JWT tokens and how they work. I learned about password hashing 
  using bcrypt. I learned about database migrations for user management."
  ```

---

## Where Does the Reference Text Come From?

### For Chairperson Summary (`ChairSummaryController.php`)

```php
// Step 1: Fetch raw data from database
$query = DB::table('weekly_entries as we')
    ->select('we.week_number', 'we.tasks', 'we.learnings')
    ->join('students as s', 's.id', '=', 'we.student_id')
    ->where('s.coordinator_id', $coordinatorId);

// Step 2: Extract activities and learnings
$activities = []; // Raw tasks from database
$learnings = [];  // Raw learnings from database

foreach ($rows as $row) {
    if (!empty($row->tasks)) {
        $activities[] = strip_tags($row->tasks);
    }
    if (!empty($row->learnings)) {
        $learnings[] = strip_tags($row->learnings);
    }
}

// Step 3: Build reference text
$referenceText = $this->buildReferenceText($activities, $learnings);
// Result: "Activities: [task1] [task2] ... Learnings: [learning1] [learning2] ..."
```

### For Coordinator Summary (`SummaryController.php`)

```php
// Step 1: Fetch raw reports from database
$reports = DB::table('weekly_entries as we')
    ->select('we.tasks as activities', 'we.learnings')
    ->where('we.student_id', $studentId)
    ->get();

// Step 2: Build reference text
$referenceText = $this->buildReferenceText($reports, $analysisType);
// Result: "Activities: [task1] Learnings: [learning1] Activities: [task2] ..."
```

---

## How the Comparison Works

### ROUGE-1 (Unigram Overlap)
- **Compares**: Individual words
- **Measures**: How many words from the OpenAI summary appear in the raw database data
- **Example**:
  - Summary: "student worked authentication system"
  - Reference: "I worked on implementing user authentication system"
  - Overlap: "worked", "authentication", "system"
  - Score: Based on precision (overlap/summary words) and recall (overlap/reference words)

### ROUGE-2 (Bigram Overlap)
- **Compares**: Two-word phrases
- **Measures**: How many 2-word phrases from the OpenAI summary appear in the raw database data
- **Example**:
  - Summary: "user authentication", "JWT tokens"
  - Reference: "implementing user authentication", "learned about JWT tokens"
  - Overlap: "user authentication", "JWT tokens"
  - Score: Based on precision and recall of bigrams

### ROUGE-L (Longest Common Subsequence)
- **Compares**: Sentence structure and word order
- **Measures**: Longest sequence of words that appear in the same order in both texts
- **Example**:
  - Summary: "student worked on authentication"
  - Reference: "I worked on implementing authentication system"
  - LCS: "worked on authentication"
  - Score: Based on how well the summary preserves the original sentence structure

### BERT Score (Semantic Similarity)
- **Compares**: Meaning and context (not just exact words)
- **Measures**: Semantic similarity using word embeddings
- **Example**:
  - Summary: "student created login forms"
  - Reference: "I built user login interface"
  - Similarity: High (even though words are different, meaning is similar)
  - Score: Based on semantic similarity between words

---

## Why Compare Against Raw Database Data?

### Purpose:
1. **Quality Assurance**: Verify that OpenAI summaries accurately reflect the original student work
2. **Accuracy Tracking**: Measure how well the AI captures the key information
3. **Debugging**: Identify if summaries are missing important details or adding incorrect information
4. **Capstone Defense**: Provide evidence that the summarization system is working correctly

### What High Scores Mean:
- **High ROUGE-1**: Summary uses similar words as the original
- **High ROUGE-2**: Summary preserves key phrases from the original
- **High ROUGE-L**: Summary maintains the original sentence structure
- **High BERT Score**: Summary captures the same meaning as the original (even with different words)

### What Low Scores Mean:
- **Low Scores**: Summary might be missing important details, using different terminology, or not accurately representing the original work

---

## Code Location

### Evaluation Service
- **File**: `app/Services/OpenAI/SummaryEvaluationService.php`
- **Method**: `evaluate($generatedSummary, $referenceText)`
- **Parameters**:
  - `$generatedSummary`: OpenAI-generated summary text
  - `$referenceText`: Raw database data (activities + learnings)

### Reference Text Building
- **Chairperson**: `app/Http/Controllers/Api/ChairSummaryController.php` → `buildReferenceText()`
- **Coordinator**: `app/Http/Controllers/Api/SummaryController.php` → `buildReferenceText()`
- **OpenAI Controllers**: `app/Http/Controllers/Api/OpenAI/ChairpersonOpenAISummaryController.php` → `buildReferenceText()`

---

## Example Flow

```
1. Student submits weekly entry:
   - Tasks: "I worked on user authentication"
   - Learnings: "I learned about JWT tokens"
   
2. Data stored in database:
   - weekly_entries.tasks = "I worked on user authentication"
   - weekly_entries.learnings = "I learned about JWT tokens"

3. OpenAI generates summary:
   - "For this week, the student worked on user authentication and learned about JWT tokens."

4. Reference text built from database:
   - "Activities: I worked on user authentication Learnings: I learned about JWT tokens"

5. Evaluation compares:
   - Generated: "student worked on user authentication and learned about JWT tokens"
   - Reference: "I worked on user authentication I learned about JWT tokens"
   
6. Scores calculated:
   - ROUGE-1: Measures word overlap
   - ROUGE-2: Measures phrase overlap
   - ROUGE-L: Measures sentence structure
   - BERT Score: Measures semantic similarity
```

---

## Summary

**ROUGE and BERT scores compare:**
- ✅ **OpenAI-generated summary** (what the AI created)
- ✅ **vs. Raw database data** (original student activities + learnings)

**The reference text is:**
- ✅ Built from `weekly_entries.tasks` and `weekly_entries.learnings` columns
- ✅ Combined as: `"Activities: [tasks] Learnings: [learnings]"`
- ✅ Used as the "ground truth" for comparison

**Purpose:**
- ✅ Track if OpenAI summaries accurately represent the original student work
- ✅ Measure summary quality for capstone defense
- ✅ Debug and improve the summarization system

