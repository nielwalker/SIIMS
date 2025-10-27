<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentHomeResource;
use App\Http\Resources\StudentProfileResource;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class StudentHomePageController extends Controller
{

    /**
     * The work post controller
     */
    private $workPostController;

    /**
     *StudentHomePageController constructor
     */
    public function __construct(WorkPostController $workPostController) {
        $this->workPostController = $workPostController;
    }

    /**
     * Summary of getHome: A public function that gets the data needed for Hompage (Student)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHome() {

        // Get authenticated user
        $authUser = Auth::user()->student;

        // Find student profile by ID
        $profileData = Student::with(['workExperiences', 'educations', 'user', 'program.college', 'coordinator.user', 'latestApplication'])->find($authUser->id);
       
        /**
         * Declare the variables necessary
         */
        $jobPosts = null;

        /**
         * Check if student status ID is 1 (Not Yet Applied) and has an coordinator, then display work posts
         */
        if($authUser->student_status_id == 1 && $authUser->coordinator_id) {
            
            // Get job posts

        }

        // Return resource
        return $this->jsonResponse(new StudentHomeResource($profileData));
    }

    private function fetchWorkPost() {

    }
}
