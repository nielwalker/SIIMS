<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoordinatorStudentApplicationController extends Controller
{

    public function getStudentApplications(String $student_id) {
        // Get auth coordinator
        $user = Auth::user();
        if (!$user || !$user->coordinator) {
            return response()->json(['message' => 'User not authenticated as coordinator.'], 401);
        }
        $auth = $user->coordinator;

        // Check if coordinator does exist in the program
        if(!$auth->program_id) {
            return response()->json(['message' => 'Coordinator not found. Unauthorized'], 404);
        }

        // Get a student under coordinator
        $student = Student::where('coordinator_id', $auth->id)->find($student_id);
        if(!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Find all student applications
        $applications = Application::where('student_id', $student->id)->get();
        if(!$applications) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        return response()->json($applications, 200);

    }

    //
    public function getStudentApplicationById(String $student_id, String $application_id) {

        // Get auth coordinator
        $user = Auth::user();
        if (!$user || !$user->coordinator) {
            return response()->json(['message' => 'User not authenticated as coordinator.'], 401);
        }
        $auth = $user->coordinator;

        // Check if coordinator does exist in the program
        if(!$auth->program_id) {
            return response()->json(['message' => 'Coordinator not found. Unauthorized'], 404);
        }

        // Get a student under coordinator
        $student = Student::where('coordinator_id', $auth->id)->find($student_id);
        if(!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Find student application
        $application = Application::with(['documentSubmissions.documentType', 'workPost', 'documentSubmissions.status'])->where('student_id', $student->id)->find($application_id);
        if(!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        return response()->json($application, 200);
    }
}
