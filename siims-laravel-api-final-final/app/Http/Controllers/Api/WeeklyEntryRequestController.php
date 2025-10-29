<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WeeklyEntryRequestController extends Controller
{
    /**
     * Coordinator: create a weekly entry request for a student and week.
     */
    public function create(Request $request)
    {
        $studentId = (string) $request->input('student_id');
        $week = (int) $request->input('week_number');
        if (!$studentId || !$week) {
            return response()->json(['error' => 'student_id and week_number are required'], 422);
        }

        $coordinatorId = Auth::id();
        $now = now();
        // Upsert-like behavior: if a pending request exists, keep it; if completed exists, create new pending
        $existing = DB::table('weekly_entry_requests')
            ->where('student_id', $studentId)
            ->where('week_number', $week)
            ->whereNull('completed_at')
            ->first();
        if ($existing) {
            return response()->json(['message' => 'Request already pending'], 200);
        }

        DB::table('weekly_entry_requests')->insert([
            'student_id' => $studentId,
            'coordinator_id' => $coordinatorId,
            'week_number' => $week,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return response()->json(['message' => 'Request created'], 201);
    }

    /**
     * Student: list my pending requests
     */
    public function myPending()
    {
        $studentId = Auth::id();
        $list = DB::table('weekly_entry_requests')
            ->select('id', 'week_number', 'coordinator_id', 'created_at')
            ->where('student_id', $studentId)
            ->whereNull('completed_at')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['data' => $list], 200);
    }

    /**
     * Student: mark a request completed by week number (when submitting the week report)
     */
    public function completeByWeek(Request $request)
    {
        $studentId = Auth::id();
        $week = (int) $request->input('week_number');
        if (!$week) {
            return response()->json(['error' => 'week_number is required'], 422);
        }
        $now = now();
        DB::table('weekly_entry_requests')
            ->where('student_id', $studentId)
            ->where('week_number', $week)
            ->whereNull('completed_at')
            ->update(['completed_at' => $now, 'updated_at' => $now]);
        return response()->json(['message' => 'Request completed'], 200);
    }
}


