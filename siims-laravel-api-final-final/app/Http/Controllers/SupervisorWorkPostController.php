<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupervisorWorkPostRequest;
use App\Models\Office;
use App\Models\Supervisor;
use App\Models\WorkPost;
use App\Models\WorkType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorWorkPostController extends Controller
{
    
    /**
     * Summary of deleteWorkPostById: A public function that deletes a work post by work_post_id
     * @param string $work_post_id
     * @return JsonResponse|mixed
     */
    public function deleteWorkPostById(String $work_post_id) {
       // Get auth user
       $user = Auth::user();
       // Find supervisor by office_id
       $supervisor = Supervisor::find($user->id);
       // Check if supervisor exist
       if(!$supervisor) {
           return response()->json(['message' => 'Supervisor not found. Unauthorized'], 404);
       }

       // Find Office by supervisor Id
       $office = Office::where('supervisor_id', $supervisor->id)->first();

       // Find work post where office_id exist and find by work_post_id
       $work_post = WorkPost::where('office_id', $office->id)->find($work_post_id);

       // Check if work post does exist
       if(!$work_post) {
           return response()->json(['message' => 'Work Office not found. Unauthorized'], 404);
       }

       // Delete work post
       $work_post->delete();

       return response()->json(['message' => 'Work post is deleted.'], 201);
    }

    /**
     * Summary of updateWorkPostById: Update work post by work_post_id
     * @param \App\Http\Requests\SupervisorWorkPostRequest $request
     * @param string $work_post_id
     * @return JsonResponse|mixed
     */
    public function updateWorkPostById(SupervisorWorkPostRequest $request, String $work_post_id ) {
        // Get auth user
        $user = Auth::user();
        // Find supervisor by office_id
        $supervisor = Supervisor::find($user->id);
        // Check if supervisor exist
        if(!$supervisor) {
            return response()->json(['message' => 'Supervisor not found. Unauthorized'], 404);
        }

        // Find Office by supervisor Id
        $office = Office::where('supervisor_id', $supervisor->id)->first();

        // Find work post where office_id exist and find by work_post_id
        $work_post = WorkPost::where('office_id', $office->id)->find($work_post_id);

        // Check if work post does exist
        if(!$work_post) {
            return response()->json(['message' => 'Work Office not found. Unauthorized'], 404);
        }

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Validate start_date and end_date
        $start_date = $validatedCredentials['start_date'];
        $end_date = $validatedCredentials['end_date'];

        // Convert to Carbon instances to compare dates
        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date);

        // Check if start_date is greater than end_date
        if ($start_date->greaterThan($end_date)) {
            return response()->json(['message' => 'Start date cannot be greater than end date.'], 400);
        }

        // Update work posts
        $work_post->work_type_id = $validatedCredentials['work_type_id'];
        $work_post->title = $validatedCredentials['title'];
        $work_post->responsibilities = $validatedCredentials['responsibilities'];
        $work_post->qualifications = $validatedCredentials['qualifications'];
        $work_post->max_applicants = $validatedCredentials['max_applicants'];
        $work_post->start_date = $validatedCredentials['start_date'];
        $work_post->end_date = $validatedCredentials['end_date'];
        $work_post->work_duration = $validatedCredentials['work_duration'];

        // Save changes
        $work_post->save();

        return response()->json(['message' => 'Work post updated successfully.'], 201);
     
    }


    public function store(SupervisorWorkPostRequest $request)
    {

        // Get validated credentials
        $validatedData = $request->validated();

        // Get authenticated user
        $user = Auth::user();
        // Find office by user_id
        $office = $this->findOffice($user['id']);

        // Create a Work Post
        $workPost = new WorkPost();
        // Fill work post
        $workPost->fill(array_merge($validatedData, ["office_id" => $office['id']]));

        // Save work post
        $workPost->save();

        return response()->json($workPost);

        // Return work posts with status 201
        // return response()->json(['message' => 'A new work is created successfully.'], 201);
    }

    /**
     * Summary of transform: Transform the fetched work posts data
     * @param mixed $work_post
     * @return array
     */
    public function transform($work_post)
    {
        return [

            "id" => $work_post['id'],
            "office_id" => $work_post['office_id'],
            "work_type_id" => $work_post['work_type_id'],
            "work_type_name" => $work_post->workType->name ?? "No work type name...",
            "title" => $work_post['title'],
            "responsibilities" => $work_post['responsibilities'],
            "qualifications" => $work_post['qualifications'],
            "max_applicants" => $work_post['max_applicants'],
            "start_date" => $this->formatDateOnlyDate($work_post['start_date']),
            "end_date" => $this->formatDateOnlyDate($work_post['end_date']),
            "work_duration" => $work_post['work_duration'],
            "created_at" => $this->formatDate($work_post['created_at']),
            "updated_at" => $this->formatDate($work_post['updated_at']),

        ];
    }

    /**
     * Summary of findOffice: Find office by supervisor_id (user_id)
     * @param mixed $supervisor_id
     * @return \App\Models\Office
     */
    private function findOffice($supervisor_id): Office
    {

        // Find office by user_id(supervisor_id)
        $office = Office::firstWhere('supervisor_id', $supervisor_id);
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Return office
        return $office;
    }

    /**
     * Summary of getJobById: A public function that gets a job by ID
     * @param string $job_id
     * @return JsonResponse|mixed
     */
    public function getJobById(String $job_id) {
        // Get auth user
        $user = Auth::user();

            // Find supervisor
        $supervisor = Supervisor::find($user['id']);
        // Check if supervisor does exit
        if(!$supervisor) {
            return response()->json(['message' => 'Supervisor not found. Unauthorized'], 404);
        }

        // Find the supervisor where office it belongs
        $office = Office::where('supervisor_id', $supervisor['id'])->first();
        // Check if it is assigned in the office
        if(!$office) {
            return response()->json(['message' => "You are not assigned."]);
        }

         // Get work post
         $workPost = WorkPost::where('office_id', $office['id'])->with(['workType'])->find($job_id);

        // Get work types
        $work_types = WorkType::all();

         // Return job post
         return response()->json([
            "initial_work_post" => $workPost,
            "work_types" => $work_types
         ]);
    }

    /**
     * Summary of index: Get work posts by office_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllJobs(): JsonResponse
    {   
        // Get auth user
        $user = Auth::user();

        // Find supervisor
        $supervisor = Supervisor::find($user['id']);
        // Check if supervisor does exit
        if(!$supervisor) {
            return response()->json(['message' => 'Supervisor not found. Unauthorized'], 404);
        }

        // Find the supervisor where office it belongs
        $office = Office::where('supervisor_id', $supervisor['id'])->first();
        // Check if it is assigned in the office
        if(!$office) {
            return response()->json(['message' => "You are not assigned."]);
        }

        // Get work posts and transform
        $workPosts = WorkPost::where('office_id', $office['id'])->with(['workType'])->get();
        $transformedWorkPosts = $workPosts->map(function ($workPost) {
            return $this->transform($workPost);
        });

        // Fetch all the job types
        $workTypes = WorkType::all();

        // Return work posts with status 200
        return response()->json([
            "initial_work_posts" => $transformedWorkPosts,
            "work_types" => $workTypes,
        ], 200);
    }
}
