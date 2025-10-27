<?php

namespace App\Http\Controllers;

use App\Models\WorkPost;
use App\Services\ActionLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentWorkPostController extends Controller
{

    public function getCurrentlyAppliedJob() {

        // Get auth student
        $authStudent = Auth::user()->student;

        // Find the most recent application the student made
        $latestApplication = $authStudent->applications()
        ->latest()  // Sort applications by 'created_at' in descending order
        ->first();  // Get the most recent application

         // If an application exists, return the associated WorkPost (the job)
         if (!$latestApplication) {
            return null;
        }   

        return response()->json([
            'application_id' => $latestApplication->id,
            'currently_applied_work_post' => $latestApplication->workPost,
            "application_status" => $latestApplication->status_type_id,
        ], 200); // Assuming 'workPost' relationship in Application

    }

    // Transform job format
    public function transform($job) {
        return [
            "id" => $job->id,
            'title' => $job->title,
            "responsibilities" => $job->responsibilities,
            "qualifications" => $job->qualifications,
            "max_applicants" => $job->max_applicants,
            "start_date"=> $this->formatDateOnlyDate($job->start_date),
            "end_date"=> $this->formatDateOnlyDate($job->end_date),
            "work_duration"=> $job->work_duration,
            "company_id" => $job->office->company->id,
            "company_name" => $job->office->company->name,
            "office_name" => $job->office->name,
            "street"=> $job->office->street,
            "barangay"=> $job->office->barangay,
            "city_municipality"=> $job->office->city_municipality,
            "province"=> $job->office->province,
            "postal_code"=> $job->office->postal_code,
            "is_closed" => Carbon::now()->greaterThan($job->end_date),
        ];
    }

    /**
     * A public function that gets the job by ID
     * @param string $job_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getJob(String $job_id, ActionLogger $actionLogger) {
        // Find job by id
        $job = WorkPost::has('office')->with(['workType', 'office', 'office.company'])->find($job_id);
        // Check if job exist
        if(!$job) {
            return response()->json(['message' => 'Job not found.']);
        }

        // Transform
        $transformedJob = $this->transform($job);

        // Return
        return response()->json($transformedJob, 200);
    }

    // Get jobs list
    public function getAllJobs() {
        
        
        // Get work posts
        $jobs = WorkPost::has('office')->with(['workType', 'office', 'office.company'])->get();
      
        // Check if work post exist
        if(!$jobs) {
            return response()->json(["message" => "Jobs not found..."]);
        }
        
        // Transform Job Post
        $transformedJobs = $jobs->map(function ($job) {
            return $this->transform($job);
        });

        // Get student status
        $user = Auth::user()->student;

        // Return
        return response()->json([
            "initial_job_posts" => $transformedJobs,
            "student" => $user,
        ], 200);
    }
}
