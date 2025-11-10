<?php

namespace App\Services;

/**
 * NLP-based Summarization Service
 * 
 * Provides extractive summarization using sentence scoring and ranking
 * Replaces OpenAI for faster, local summarization
 */
class NLPSummarizationService
{
    /**
     * Generate summary for coordinator
     * 
     * @param string $text Combined learnings text
     * @param int|null $week Week number or null for overall
     * @return string Summary in third person format
     */
    public function summarizeForCoordinator(string $text, ?int $week = null): string
    {
        if (empty(trim($text))) {
            $weekLabel = $week ? "week {$week}" : "the selected week";
            return "In {$weekLabel} this student completed their weekly activities and learning outcomes.";
        }

        $summary = $this->extractiveSummarize($text, 2, 3); // 2-3 sentences
        
        // Format with week prefix
        $weekLabel = $week ? "week {$week}" : "the selected week";
        $prefix = "In {$weekLabel} this student ";
        
        // Ensure third person
        $summary = $this->convertToThirdPerson($summary);
        
        // Add prefix if not already present
        if (!preg_match('/^In\s+(week\s+\d+|the\s+selected\s+week)\s+this\s+student/i', $summary)) {
            $summary = $prefix . ltrim($summary);
        }
        
        return $summary;
    }

    /**
     * Generate summary for chairperson
     * 
     * @param string $text Combined activities and learnings text
     * @param int|null $week Week number or null for overall
     * @return string Summary in third person format
     */
    public function summarizeForChairperson(string $text, ?int $week = null): string
    {
        if (empty(trim($text))) {
            if ($week) {
                return "For this week, those students completed their weekly activities and learning outcomes.";
            }
            return "Overall, those students completed their internship activities and learning outcomes.";
        }

        $summary = $this->extractiveSummarize($text, 3, 5); // 3-5 sentences for chairperson
        
        // Ensure third person
        $summary = $this->convertToThirdPerson($summary);
        
        // Add week prefix if needed
        if ($week) {
            if (!preg_match('/^For\s+this\s+week/i', $summary)) {
                $summary = "For this week, those students " . lcfirst($summary);
            }
        } else {
            if (!preg_match('/^(Overall|In\s+summary)/i', $summary)) {
                $summary = "Overall, those students " . lcfirst($summary);
            }
        }
        
        return $summary;
    }

    /**
     * Extractive summarization using sentence scoring
     * 
     * @param string $text Input text
     * @param int $minSentences Minimum sentences in summary
     * @param int $maxSentences Maximum sentences in summary
     * @return string Summary
     */
    private function extractiveSummarize(string $text, int $minSentences = 2, int $maxSentences = 3): string
    {
        // Clean and normalize text
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (empty($text)) {
            return '';
        }

        // Split into sentences
        $sentences = $this->splitIntoSentences($text);
        
        if (count($sentences) <= $minSentences) {
            return implode(' ', $sentences);
        }

        // Calculate word frequencies (TF)
        $wordFrequencies = $this->calculateWordFrequencies($text);
        
        // Score each sentence
        $sentenceScores = [];
        foreach ($sentences as $index => $sentence) {
            $score = $this->scoreSentence($sentence, $wordFrequencies, $index, count($sentences));
            $sentenceScores[$index] = $score;
        }

        // Sort sentences by score (descending)
        arsort($sentenceScores);
        
        // Select top sentences (maintain original order)
        $selectedIndices = array_slice(array_keys($sentenceScores), 0, $maxSentences, true);
        sort($selectedIndices); // Maintain original order
        
        // Build summary
        $summarySentences = [];
        foreach ($selectedIndices as $index) {
            $summarySentences[] = $sentences[$index];
        }
        
        // Ensure we have at least minSentences
        if (count($summarySentences) < $minSentences && count($sentences) >= $minSentences) {
            // Add more sentences if needed
            $remaining = array_diff(array_keys($sentences), $selectedIndices);
            $needed = $minSentences - count($summarySentences);
            foreach (array_slice($remaining, 0, $needed) as $index) {
                $summarySentences[] = $sentences[$index];
            }
            sort($summarySentences);
        }
        
        return implode(' ', $summarySentences);
    }

    /**
     * Split text into sentences
     * 
     * @param string $text
     * @return array
     */
    private function splitIntoSentences(string $text): array
    {
        // Split by sentence terminators
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Clean and filter sentences
        $sentences = array_map(function($s) {
            $s = trim($s);
            // Remove extra punctuation
            $s = preg_replace('/[.!?]+$/', '', $s);
            return trim($s);
        }, $sentences);
        
        // Filter out very short sentences
        $sentences = array_filter($sentences, function($s) {
            return mb_strlen($s) > 10;
        });
        
        return array_values($sentences);
    }

    /**
     * Calculate word frequencies (Term Frequency)
     * 
     * @param string $text
     * @return array
     */
    private function calculateWordFrequencies(string $text): array
    {
        // Convert to lowercase
        $text = mb_strtolower($text);
        
        // Remove punctuation and split into words
        $text = preg_replace('/[^\w\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Remove stop words (common words that don't add meaning)
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them'];
        
        $words = array_filter($words, function($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        // Count frequencies
        $frequencies = array_count_values($words);
        
        // Normalize by max frequency
        $maxFreq = max($frequencies) ?: 1;
        foreach ($frequencies as $word => $freq) {
            $frequencies[$word] = $freq / $maxFreq;
        }
        
        return $frequencies;
    }

    /**
     * Score a sentence based on word frequencies and position
     * 
     * @param string $sentence
     * @param array $wordFrequencies
     * @param int $position Sentence position (0-based)
     * @param int $totalSentences Total number of sentences
     * @return float Score
     */
    private function scoreSentence(string $sentence, array $wordFrequencies, int $position, int $totalSentences): float
    {
        $score = 0.0;
        
        // Convert sentence to lowercase and extract words
        $sentenceLower = mb_strtolower($sentence);
        $sentenceLower = preg_replace('/[^\w\s]/u', ' ', $sentenceLower);
        $words = preg_split('/\s+/', $sentenceLower, -1, PREG_SPLIT_NO_EMPTY);
        
        // Sum word frequencies
        foreach ($words as $word) {
            if (isset($wordFrequencies[$word])) {
                $score += $wordFrequencies[$word];
            }
        }
        
        // Normalize by sentence length
        $wordCount = count($words);
        if ($wordCount > 0) {
            $score = $score / $wordCount;
        }
        
        // Boost score for sentences at the beginning (often contain key information)
        $positionBonus = 1.0;
        if ($position < 3) {
            $positionBonus = 1.2; // 20% boost for first 3 sentences
        } elseif ($position < $totalSentences * 0.3) {
            $positionBonus = 1.1; // 10% boost for first 30% of sentences
        }
        
        $score *= $positionBonus;
        
        // Boost for longer sentences (but not too long)
        $lengthBonus = 1.0;
        if ($wordCount >= 10 && $wordCount <= 30) {
            $lengthBonus = 1.1; // Prefer medium-length sentences
        } elseif ($wordCount > 30) {
            $lengthBonus = 0.9; // Penalize very long sentences
        }
        
        $score *= $lengthBonus;
        
        return $score;
    }

    /**
     * Convert text to third person
     * 
     * @param string $text
     * @return string
     */
    private function convertToThirdPerson(string $text): string
    {
        // Replace first person pronouns
        $replacements = [
            '/\bI\b/i' => 'the student',
            '/\bme\b/i' => 'the student',
            '/\bmy\b/i' => 'the student\'s',
            '/\bmyself\b/i' => 'the student',
            '/\bwe\b/i' => 'the students',
            '/\bus\b/i' => 'the students',
            '/\bour\b/i' => 'the students\'',
            '/\bourselves\b/i' => 'the students',
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        // Fix verb forms
        $text = preg_replace('/\bam\b/i', 'is', $text);
        $text = preg_replace('/\bare\b/i', 'are', $text);
        $text = preg_replace('/\bwas\b/i', 'was', $text);
        $text = preg_replace('/\bwere\b/i', 'were', $text);
        
        return $text;
    }
}

