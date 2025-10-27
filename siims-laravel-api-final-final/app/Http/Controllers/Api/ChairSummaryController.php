<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ChairSummaryAdapter;

class ChairSummaryController extends Controller
{
    public function generate(Request $request, ChairSummaryAdapter $adapter): JsonResponse
    {
        $coordinatorId = $request->input('coordinatorId');
        $week = $request->integer('week');
        $useGPT = (bool) $request->input('useGPT');

        // Fetch weekly entries of all students under coordinator
        $query = DB::table('weekly_entries as we')
            ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
            ->join('students as s', 's.id', '=', 'we.student_id');

        if ($coordinatorId) {
            $query->where('s.coordinator_id', $coordinatorId);
        }
        if ($week) {
            $query->where('we.week_number', $week);
        }

        $rows = $query->get();
        \Log::info('ChairSummary: Found ' . $rows->count() . ' weekly entries for coordinator ' . $coordinatorId . ', week ' . $week);
        
        $combined = $rows->map(function ($r) {
            $t = trim(($r->tasks ?? '') . ' ' . ($r->learnings ?? ''));
            $t = preg_replace('/\s+/', ' ', $t);
            if ($t && !preg_match('/[.!?]$/', $t)) { $t .= '.'; }
            return $t;
        })->filter()->implode(' ');

        // Enforce third-person phrasing before summarization (handles fallback too)
        $combined = $this->convertToThirdPerson($combined);

        \Log::info('ChairSummary: Combined text length: ' . strlen($combined));
        \Log::info('ChairSummary: Combined text preview: ' . substr($combined, 0, 200));

        // Use adapter summarize method (analyze not defined)
        $result = $adapter->summarize($combined, $week, $useGPT);
        // Ensure result summary is also third-person (extra safety for any model variation)
        if (isset($result['summary'])) {
            $result['summary'] = $this->convertToThirdPerson($result['summary']);
            if (!empty($week)) {
                $result['summary'] = $this->enforceWeekPrefix($result['summary']);
            }
        }
        
        \Log::info('ChairSummary: Result from adapter:', $result);

        // For "overall" week, provide structured data for frontend
        if ($week === null || $week === 0) {
            // Extract activities and learnings from the raw data
            $activities = [];
            $learnings = [];
            
            foreach ($rows as $row) {
                if (!empty($row->tasks)) {
                    // Clean HTML tags and normalize text
                    $cleanTasks = strip_tags($row->tasks);
                    $cleanTasks = preg_replace('/\s+/', ' ', $cleanTasks);
                    $cleanTasks = trim($cleanTasks);
                    
                    // Convert first person to third person
                    $cleanTasks = $this->convertToThirdPerson($cleanTasks);
                    
                    if (!empty($cleanTasks)) {
                        $activities[] = $cleanTasks;
                    }
                }
                if (!empty($row->learnings)) {
                    // Clean HTML tags and normalize text
                    $cleanLearnings = strip_tags($row->learnings);
                    $cleanLearnings = preg_replace('/\s+/', ' ', $cleanLearnings);
                    $cleanLearnings = trim($cleanLearnings);
                    
                    // Convert first person to third person
                    $cleanLearnings = $this->convertToThirdPerson($cleanLearnings);
                    
                    if (!empty($cleanLearnings)) {
                        $learnings[] = $cleanLearnings;
                    }
                }
            }
            
            // Remove duplicates and clean up
            $activities = array_unique(array_filter($activities));
            $learnings = array_unique(array_filter($learnings));
            
            $result['corrected_activities'] = $activities;
            $result['corrected_learnings'] = $learnings;
            $result['summary for this section on a week'] = $result['summary'] ?? 'Students demonstrated comprehensive learning and skill development through various activities.';
        }

        return response()->json($result, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    private function enforceWeekPrefix(string $text): string
    {
        $t = trim($text);
        if ($t === '') return $t;
        if (preg_match('/^For\s+this\s+week,\s+those\s+students/i', $t)) {
            return $t;
        }
        $t = preg_replace('/^(In\s+week\s+\d+\s*,\s*|This\s+week\s*,\s*|In\s+this\s+week\s*,\s*)/i', '', $t);
        return 'For this week, those students ' . ltrim($t);
    }
    
    private function convertToThirdPerson($text)
    {
        if (!is_string($text) || $text === '') return $text;

        $replacements = [
            // First-person singular contractions and phrases
            '/\bI\'m\b/i' => 'the student is',
            '/\bI\'ve\b/i' => 'the student has',
            '/\bI\'d\b/i' => 'the student would',
            '/\bI\'ll\b/i' => 'the student will',
            '/\bI was able to\b/i' => 'the student was able to',
            '/\bI was\b/i' => 'the student was',
            '/\bI am\b/i' => 'the student is',
            '/\bI have\b/i' => 'the student has',
            '/\bI had\b/i' => 'the student had',
            '/\bI can\b/i' => 'the student can',
            '/\bI could\b/i' => 'the student could',
            '/\bI learned\b/i' => 'the student learned',
            '/\bI became\b/i' => 'the student became',
            '/\bI gained\b/i' => 'the student gained',
            '/\bI developed\b/i' => 'the student developed',
            '/\bI acquired\b/i' => 'the student acquired',
            '/\bI improved\b/i' => 'the student improved',
            '/\bI enhanced\b/i' => 'the student enhanced',
            '/\bI\b/i' => 'the student',
            '/\bme\b/i' => 'the student',
            '/\bmyself\b/i' => 'themselves',
            '/\bmy\b/i' => 'the student\'s',

            // First-person plural
            '/\bwe\'re\b/i' => 'the students are',
            '/\bwe\'ve\b/i' => 'the students have',
            '/\bwe\'d\b/i' => 'the students would',
            '/\bwe\'ll\b/i' => 'the students will',
            '/\bwe were able to\b/i' => 'the students were able to',
            '/\bwe were\b/i' => 'the students were',
            '/\bwe are\b/i' => 'the students are',
            '/\bwe have\b/i' => 'the students have',
            '/\bwe had\b/i' => 'the students had',
            '/\bwe can\b/i' => 'the students can',
            '/\bwe could\b/i' => 'the students could',
            '/\bwe learned\b/i' => 'the students learned',
            '/\bwe\b/i' => 'the students',
            '/\bus\b/i' => 'the students',
            '/\bour\b/i' => 'the students\'',
            '/\bours\b/i' => 'the students\'',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        return $text;
    }
}


