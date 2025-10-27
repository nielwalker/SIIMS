<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Office;
use App\Models\WorkPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorApplicantController extends Controller
{

    public function getApplicantApplicationById(String $application_id) {
        
        // Get auth user
        $auth = Auth::user()->supervisor;

        // Find supervisor office
        $office = Office::where('supervisor_id', $auth->id)->first();
        if(!$office) {
            return response()->json(['message' => 'Office not found.']);
        }


        // Find applicant's application by ID
        $application = Application::with(['student.user', 'workPost'])->find($application_id);
        if(!$application) {
            return response()->json(['message' => 'Application not found']);
        }


        // Return application
        return response()->json($application, 200);
    }

    private function transform($applicant) {
        return [
            "id" => $applicant->id,
            "job_title" => $applicant->workPost ? $applicant->workPost->title : "Invalid Work Post",
            "student_id" =>  $applicant->student ? $applicant->student->id : "",
            "first_name" => $applicant->student ? $applicant->student->user->first_name : "",
            "middle_name" => $applicant->student ? $applicant->student->user->middle_name : "",
            "last_name" => $applicant->student ? $applicant->student->user->last_name : "",
            "phone_number" => $applicant->student ? $applicant->student->user->phone_number : "",
            "status" => $applicant->student ? $applicant->student->status->name : "",
        ];
    }

    public function getAllApplicants() {
        // Get authenticated user's supervisor details
        $auth = Auth::user()->supervisor;
    
        // Find the office by supervisor_id
        $office = Office::where('supervisor_id', $auth->id)->first();
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Get all work post IDs by office_id
        $workPostIds = WorkPost::where('office_id', $office->id)->pluck('id'); // Only retrieve IDs
        if ($workPostIds->isEmpty()) {
            return response()->json(['message' => 'Work posts not found.'], 404);
        }

        // Collect all applicants for the retrieved work post IDs
        $applicants = Application::with(['student.user', 'workPost', 'student.status'])
                                    ->whereIn('work_post_id', $workPostIds)
                                    ->get();
    
        // Check if applicants exist
        if ($applicants->isEmpty()) {
            return response()->json(['message' => 'No applicants found.'], 404);
        }
    
        // Transform applicants
        $transformedApplicants = $applicants->map(function ($applicant) {
            return $this->transform($applicant); // Assuming transform() formats the applicant data
        });
    
        // Return the formatted data
        return response()->json($transformedApplicants, 200);
    }
    

    
}
