<?php

namespace App\Http\Controllers\Api\Chairperson;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChairpersonWeeksController extends Controller
{
    /**
     * Get available weeks for a coordinator (optimized single query)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableWeeks(Request $request): JsonResponse
    {
        $coordinatorId = $request->input('coordinatorId');
        $sectionId = $request->input('sectionId');

        if (!$coordinatorId) {
            return response()->json([
                'weeks' => [],
                'sections' => []
            ], 200);
        }

        try {
            // Single optimized query to get distinct weeks for coordinator
            $query = DB::table('weekly_entries as we')
                ->select('we.week_number')
                ->join('students as s', 's.id', '=', 'we.student_id')
                ->where('s.coordinator_id', $coordinatorId)
                ->whereNotNull('we.week_number')
                ->where('we.week_number', '>', 0);

            if ($sectionId) {
                $query->where('s.section_id', $sectionId);
            }

            $weeks = $query->distinct()
                ->orderBy('we.week_number', 'asc')
                ->pluck('week_number')
                ->map(fn($w) => (int)$w)
                ->toArray();

            // Also get sections for this coordinator
            $sections = DB::table('sections')
                ->select('id', 'name')
                ->where('coordinator_id', $coordinatorId)
                ->get()
                ->map(fn($s) => ['id' => $s->id, 'name' => $s->name])
                ->toArray();

            return response()->json([
                'weeks' => $weeks,
                'sections' => $sections
            ], 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        } catch (\Exception $e) {
            Log::error('ChairpersonWeeksController error', [
                'error' => $e->getMessage(),
                'coordinator_id' => $coordinatorId,
                'section_id' => $sectionId
            ]);

            return response()->json([
                'weeks' => [],
                'sections' => [],
                'error' => 'Failed to fetch weeks'
            ], 500);
        }
    }
}

