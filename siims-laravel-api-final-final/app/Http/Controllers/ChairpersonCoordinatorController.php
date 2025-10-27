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

        // Get auth user's program
        $program = $user->program;

        // Check if chairperson has a program
        if(!$program || !$program->chairperson_id) {
            return response()->json(['message' => 'Chairperson program not found.'], 404);
        }

        // Get all coordinators in the same program
        $coordinators = Coordinator::with(['user'])->withCount(['students'])->where('program_id', $program->id)->get();
        if(!$coordinators) {
            return response()->json(['message' => 'Coordinators not found.'], 404);
        }

        // Transform coordinator
        $transformedCoordinators = $coordinators->map(function ($coordinator) {
            return $this->transform($coordinator);
        });

        // Return coordinators
        return response()->json($transformedCoordinators, 200);
    }
}
