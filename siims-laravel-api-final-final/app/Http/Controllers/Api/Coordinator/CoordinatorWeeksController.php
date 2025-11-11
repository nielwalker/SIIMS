<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CoordinatorWeeksController extends Controller
{
    /**
     * Get available weeks for a student (optimized single query)
     * Coordinator can only see their own students
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableWeeks(Request $request): JsonResponse
    {
        $studentId = $request->input('studentId');
        $coordinatorId = Auth::id(); // Get authenticated coordinator ID

        if (!$studentId) {
            return response()->json([
                'weeks' => [],
                'totalHours' => 0
            ], 200);
        }

        try {
            // Verify the student belongs to this coordinator
            $student = DB::table('students')
                ->where('id', $studentId)
                ->where('coordinator_id', $coordinatorId)
                ->first();

            if (!$student) {
                return response()->json([
                    'weeks' => [],
                    'totalHours' => 0,
                    'error' => 'Student not found or access denied'
                ], 403);
            }

            // Single optimized query to get distinct weeks and total hours
            $weeksData = DB::table('weekly_entries')
                ->select(
                    'week_number',
                    DB::raw('SUM(no_of_hours) as total_hours')
                )
                ->where('student_id', $studentId)
                ->whereNotNull('week_number')
                ->where('week_number', '>', 0)
                ->groupBy('week_number')
                ->orderBy('week_number', 'asc')
                ->get();

            $weeks = $weeksData->pluck('week_number')
                ->map(fn($w) => (int)$w)
                ->toArray();

            // Calculate total hours across all weeks
            $totalHours = $weeksData->sum('total_hours') ?? 0;

            return response()->json([
                'weeks' => $weeks,
                'totalHours' => (int)$totalHours
            ], 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        } catch (\Exception $e) {
            Log::error('CoordinatorWeeksController error', [
                'error' => $e->getMessage(),
                'student_id' => $studentId,
                'coordinator_id' => $coordinatorId
            ]);

            return response()->json([
                'weeks' => [],
                'totalHours' => 0,
                'error' => 'Failed to fetch weeks'
            ], 500);
        }
    }
}

