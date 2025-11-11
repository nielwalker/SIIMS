<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChairpersonCoordinatorController extends Controller
{
    //
    private function transform($coordinator) {
        return [
            "id" => $coordinator->id,
            "first_name" => $coordinator->user->first_name,
            "middle_name" => $coordinator->user->middle_name,
            "last_name" => $coordinator->user->last_name,
            "email" => $coordinator->user->email,
            "phone_number" => $coordinator->user->phone_number,
            "total_assigned_students" => $coordinator->students_count,
        ];
    }

    //
    public function getAllCoordinators() {
        // Get auth user
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Get auth user's program (optimized - only get program_id)
        $program = $user->program;
        if (!$program || !$program->chairperson_id) {
            return response()->json(['message' => 'Chairperson program not found.'], 404);
        }

        // OPTIMIZED: Only select needed columns and use efficient eager loading
        $coordinators = Coordinator::select('id', 'program_id')
            ->with([
                'user:id,first_name,middle_name,last_name,email,phone_number' // Only load needed user fields
            ])
            ->withCount(['students']) // Efficient count
            ->where('program_id', $program->id)
            ->get();
        
        if($coordinators->isEmpty()) {
            return response()->json([], 200); // Return empty array instead of 404
        }

        // Transform coordinator
        $transformedCoordinators = $coordinators->map(function ($coordinator) {
            return $this->transform($coordinator);
        });

        // Return coordinators with CORS headers
        return response()->json($transformedCoordinators, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ]);
    }
}
