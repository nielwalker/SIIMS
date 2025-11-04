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
        $sectionId = $request->input('sectionId');
        $week = $request->integer('week');
        $useGPT = (bool) $request->input('useGPT');

        // Fetch weekly entries of all students under coordinator (and section if specified)
        $query = DB::table('weekly_entries as we')
            ->select('we.week_number as weekNumber', 'we.tasks', 'we.learnings')
            ->join('students as s', 's.id', '=', 'we.student_id');

        if ($coordinatorId) {
            $query->where('s.coordinator_id', $coordinatorId);
        }
        if ($sectionId) {
            $query->where('s.section_id', $sectionId);
            \Log::info('ChairSummary: Filtering by section_id: ' . $sectionId);
        }
        if ($week) {
            $query->where('we.week_number', $week);
        }

        $rows = $query->get();
        \Log::info('ChairSummary: Found ' . $rows->count() . ' weekly entries for coordinator ' . $coordinatorId . ', section ' . $sectionId . ', week ' . $week);
        
        // Log sample student IDs for debugging
        if ($rows->count() > 0) {
            $studentIds = DB::table('students as s')
                ->select('s.id', 's.section_id', 's.coordinator_id')
                ->where('s.coordinator_id', $coordinatorId)
                ->when($sectionId, function($q) use ($sectionId) {
                    return $q->where('s.section_id', $sectionId);
                })
                ->get();
            \Log::info('ChairSummary: Students matching criteria: ' . json_encode($studentIds));
        }
        
        // Extract activities/tasks and learnings separately for PO analysis
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
        
        // Combined text for summary generation (can vary)
        $combined = $rows->map(function ($r) {
            $t = trim(($r->tasks ?? '') . ' ' . ($r->learnings ?? ''));
            $t = preg_replace('/\s+/', ' ', $t);
            if ($t && !preg_match('/[.!?]$/', $t)) { $t .= '.'; }
            return $t;
        })->filter()->implode(' ');

        // Enforce third-person phrasing before summarization (handles fallback too)
        $combined = $this->convertToThirdPerson($combined);

        \Log::info('ChairSummary: Combined text length: ' . strlen($combined));
        \Log::info('ChairSummary: Activities count: ' . count($activities));
        \Log::info('ChairSummary: Learnings count: ' . count($learnings));
        
        // Log sample activities and learnings for debugging
        if (!empty($activities)) {
            \Log::info('ChairSummary: Sample activities: ' . json_encode(array_slice($activities, 0, 3)));
        }
        if (!empty($learnings)) {
            \Log::info('ChairSummary: Sample learnings: ' . json_encode(array_slice($learnings, 0, 3)));
        }

        // Use adapter - pass activities and learnings separately for PO analysis
        // Summary generation is separate from PO analysis
        $result = $adapter->summarize($combined, $week, $useGPT, $activities, $learnings);
        // Ensure result summary is also third-person (extra safety for any model variation)
        if (isset($result['summary'])) {
            $result['summary'] = $this->convertToThirdPerson($result['summary']);
            if (!empty($week)) {
                $result['summary'] = $this->enforceWeekPrefix($result['summary']);
            }
        }
        
        \Log::info('ChairSummary: Result from adapter:', $result);

        // Activities and learnings are already extracted and stored in result by adapter
        // For "overall" week, ensure summary format
        if ($week === null || $week === 0) {
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


