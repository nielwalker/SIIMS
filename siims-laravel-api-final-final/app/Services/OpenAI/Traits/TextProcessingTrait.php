<?php

namespace App\Services\OpenAI\Traits;

/**
 * Text Processing Trait
 * 
 * Shared text processing methods for OpenAI-related controllers and services.
 * Consolidates duplicate logic for text transformation and reference building.
 */
trait TextProcessingTrait
{
    /**
     * Convert first-person text to third-person
     * 
     * @param string $text
     * @param string $singularSubject Subject for singular (default: 'the student')
     * @param string $pluralSubject Subject for plural (default: 'the students')
     * @return string
     */
    protected function convertToThirdPerson(string $text, string $singularSubject = 'the student', string $pluralSubject = 'the students'): string
    {
        if (!is_string($text) || $text === '') {
            return $text;
        }

        $replacements = [
            // First-person singular contractions and phrases
            '/\bI\'m\b/i' => $singularSubject . ' is',
            '/\bI\'ve\b/i' => $singularSubject . ' has',
            '/\bI\'d\b/i' => $singularSubject . ' would',
            '/\bI\'ll\b/i' => $singularSubject . ' will',
            '/\bI was able to\b/i' => $singularSubject . ' was able to',
            '/\bI was\b/i' => $singularSubject . ' was',
            '/\bI am\b/i' => $singularSubject . ' is',
            '/\bI have\b/i' => $singularSubject . ' has',
            '/\bI had\b/i' => $singularSubject . ' had',
            '/\bI can\b/i' => $singularSubject . ' can',
            '/\bI could\b/i' => $singularSubject . ' could',
            '/\bI learned\b/i' => $singularSubject . ' learned',
            '/\bI became\b/i' => $singularSubject . ' became',
            '/\bI gained\b/i' => $singularSubject . ' gained',
            '/\bI developed\b/i' => $singularSubject . ' developed',
            '/\bI acquired\b/i' => $singularSubject . ' acquired',
            '/\bI improved\b/i' => $singularSubject . ' improved',
            '/\bI enhanced\b/i' => $singularSubject . ' enhanced',
            '/\bI\b/i' => $singularSubject,
            '/\bme\b/i' => $singularSubject,
            '/\bmyself\b/i' => 'themselves',
            '/\bmy\b/i' => $singularSubject . '\'s',

            // First-person plural
            '/\bwe\'re\b/i' => $pluralSubject . ' are',
            '/\bwe\'ve\b/i' => $pluralSubject . ' have',
            '/\bwe\'d\b/i' => $pluralSubject . ' would',
            '/\bwe\'ll\b/i' => $pluralSubject . ' will',
            '/\bwe were able to\b/i' => $pluralSubject . ' were able to',
            '/\bwe were\b/i' => $pluralSubject . ' were',
            '/\bwe are\b/i' => $pluralSubject . ' are',
            '/\bwe have\b/i' => $pluralSubject . ' have',
            '/\bwe had\b/i' => $pluralSubject . ' had',
            '/\bwe can\b/i' => $pluralSubject . ' can',
            '/\bwe could\b/i' => $pluralSubject . ' could',
            '/\bwe learned\b/i' => $pluralSubject . ' learned',
            '/\bwe\b/i' => $pluralSubject,
            '/\bus\b/i' => $pluralSubject,
            '/\bour\b/i' => $pluralSubject . '\'',
            '/\bours\b/i' => $pluralSubject . '\'',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }

    /**
     * Build reference text from raw database data for evaluation
     * 
     * @param array|string $activities Raw activities from database
     * @param array|string $learnings Raw learnings from database
     * @param string $assessment Optional assessment text
     * @return string Combined reference text
     */
    protected function buildReferenceText($activities, $learnings, string $assessment = ''): string
    {
        $parts = [];
        
        // Ensure activities is an array
        if (!is_array($activities)) {
            $activities = is_string($activities) && !empty($activities) ? [$activities] : [];
        }
        
        // Ensure learnings is an array
        if (!is_array($learnings)) {
            $learnings = is_string($learnings) && !empty($learnings) ? [$learnings] : [];
        }
        
        // Add activities
        if (!empty($activities)) {
            $activitiesText = array_filter(array_map('trim', $activities));
            if (!empty($activitiesText)) {
                $parts[] = 'Activities: ' . implode(' ', $activitiesText);
            }
        }
        
        // Add learnings
        if (!empty($learnings)) {
            $learningsText = array_filter(array_map('trim', $learnings));
            if (!empty($learningsText)) {
                $parts[] = 'Learnings: ' . implode(' ', $learningsText);
            }
        }
        
        // Add assessment if provided
        if (!empty($assessment)) {
            $parts[] = 'Assessment: ' . trim($assessment);
        }
        
        return implode(' ', $parts);
    }

    /**
     * Clean and extract activities and learnings from database rows
     * 
     * @param \Illuminate\Support\Collection $rows Database rows
     * @return array{activities: array, learnings: array}
     */
    protected function extractActivitiesAndLearnings($rows): array
    {
        $activities = [];
        $learnings = [];
        
        foreach ($rows as $row) {
            if (!empty($row->tasks)) {
                $cleanTasks = strip_tags($row->tasks);
                $cleanTasks = preg_replace('/\s+/', ' ', $cleanTasks);
                $cleanTasks = trim($cleanTasks);
                if (!empty($cleanTasks)) {
                    $activities[] = $cleanTasks;
                }
            }
            if (!empty($row->learnings)) {
                $cleanLearnings = strip_tags($row->learnings);
                $cleanLearnings = preg_replace('/\s+/', ' ', $cleanLearnings);
                $cleanLearnings = trim($cleanLearnings);
                if (!empty($cleanLearnings)) {
                    $learnings[] = $cleanLearnings;
                }
            }
        }
        
        // Remove duplicates
        $activities = array_values(array_unique(array_filter($activities)));
        $learnings = array_values(array_unique(array_filter($learnings)));
        
        return [
            'activities' => $activities,
            'learnings' => $learnings,
        ];
    }

    /**
     * Build combined text from activities and learnings
     * 
     * @param \Illuminate\Support\Collection $rows Database rows
     * @return string Combined text
     */
    protected function buildCombinedText($rows): string
    {
        $combined = $rows->map(function ($r) {
            $t = trim(($r->tasks ?? '') . ' ' . ($r->learnings ?? ''));
            $t = preg_replace('/\s+/', ' ', $t);
            if ($t && !preg_match('/[.!?]$/', $t)) {
                $t .= '.';
            }
            return $t;
        })->filter()->implode(' ');
        
        return $combined;
    }
}

