# OpenAI Services Organization

This folder contains all OpenAI-related code for the SIIMS application, organized for easy navigation during defense code questioning.

## Structure

```
app/Services/OpenAI/
├── OpenAIService.php              # Unified OpenAI API service (all API calls)
├── PromptBuilder.php              # Prompt builder for chairperson summaries
├── CoordinatorPromptBuilder.php   # Prompt builder for coordinator summaries
├── ChairSummaryService.php        # Service for chairperson summary with PO analysis
├── ChairSummaryPromptBuilder.php  # Prompt builder for chairperson PO analysis
└── README.md                      # This file

app/Http/Controllers/Api/OpenAI/
├── SummaryController.php          # Controller for chairperson OpenAI summaries
└── CoordinatorSummaryController.php # Controller for coordinator OpenAI summaries
```

## Key Files

### OpenAIService.php
- **Purpose**: Centralized service for all OpenAI API interactions
- **Methods**:
  - `call()` - Main method for OpenAI API calls
  - `callSimple()` - Quick call with simple prompt (backward compatibility)
  - `cleanText()` - Text cleaning utility
  - `cleanDataArray()` - Array cleaning utility
  - `enforceWeekPrefix()` - Week prefix enforcement
  - `isAvailable()` - Check if OpenAI is configured

### PromptBuilder.php
- **Purpose**: Builds prompts for chairperson summary generation
- **Methods**:
  - `buildSummaryPrompt()` - Main method to build prompts based on type
  - Supports: `overall_summary`, `chair_week`, `coordinator_week`

### CoordinatorPromptBuilder.php
- **Purpose**: Builds prompts for coordinator summaries with PO analysis
- **Methods**:
  - `buildPrompt()` - Builds coordinator summary prompt with PO word detection

### ChairSummaryService.php
- **Purpose**: Handles chairperson summary generation with comprehensive PO analysis
- **Methods**:
  - `generateSummaryWithPOAnalysis()` - Main method for generating summary with PO analysis

### Controllers
- **SummaryController.php**: Handles `/api/v1/summary/openai-summarize` endpoints
- **CoordinatorSummaryController.php**: Handles `/api/v1/summary/openai-summarize-coordinator` endpoints

## Migration Notes

### Old Locations (Deprecated)
- `app/Http/Controllers/Api/OpenAISummaryController.php` → Moved to `app/Http/Controllers/Api/OpenAI/SummaryController.php`
- `app/Http/Controllers/Api/CoordinatorOpenAISummaryController.php` → Moved to `app/Http/Controllers/Api/OpenAI/CoordinatorSummaryController.php`
- `app/Services/Traits/OpenAIServiceTrait.php` → Functionality moved to `OpenAIService.php`

### Updated Routes
All routes in `routes/summary.php` and `routes/api.php` have been updated to use the new controller locations.

## Usage Examples

### Using OpenAIService
```php
use App\Services\OpenAI\OpenAIService;

$openAIService = app(OpenAIService::class);

// Simple call
$result = $openAIService->callSimple('Generate a summary...');

// Advanced call with custom options
$result = $openAIService->call([
    ['role' => 'system', 'content' => 'You are an expert...'],
    ['role' => 'user', 'content' => 'Analyze this...']
], [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 3000,
    'temperature' => 0.2
]);
```

### Using ChairSummaryService
```php
use App\Services\OpenAI\ChairSummaryService;

$service = app(ChairSummaryService::class);
$result = $service->generateSummaryWithPOAnalysis(
    $text,
    $week,
    $activities,
    $learnings
);
```

## Benefits of This Organization

1. **Easy Navigation**: All OpenAI code is in one place
2. **No Duplication**: Unified service eliminates duplicate API call code
3. **Clean Separation**: Controllers, services, and prompt builders are clearly separated
4. **Easy to Explain**: Clear structure makes it easy to explain during defense
5. **Maintainable**: Changes to OpenAI integration only need to be made in one place

