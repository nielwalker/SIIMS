<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyWorkPostRequest;
use App\Models\Company;
use App\Models\Office;
use App\Models\WorkPost;
use App\Models\WorkType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyWorkPostController extends Controller
{   
    /**
     * Summary of getWorkPostById: A public function that gets the work post by work_post_id
     * @param string $work_post_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getWorkPostById(String $work_post_id) {

         // Get the authenticated user (company)
        $user = Auth::user();

         // Find the work post by work_post_id
         $work_post = WorkPost::with(['office', 'workType'])->where('id', $work_post_id)->whereHas('office', function ($query) use ($user) {
            $query->where('company_id', $user->id);
         })->first();
         // Check if the work post exists
    if (!$work_post) {
        return response()->json(['message' => 'Work post not found or not part of your company.'], 404);
    }
      // Return the work post details
        return response()->json($work_post, 200);
    }

    /**
     * Summary of updateWorkPostById: A public function that updates a work post by work_post_id
     * @param \App\Http\Requests\CompanyWorkPostRequest $request
     * @param string $work_post_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateWorkPostById(CompanyWorkPostRequest $request, String $work_post_id) {
        // Get validated credentials
        $validatedCredentials = $request->validated();
        // Find work post 
        $work_post = WorkPost::find($work_post_id);
        // Check if work post by id
        if(!$work_post) {
            return response()->json(['message' => 'Work post not found.'], 404);
        }

        // Update work post
        $work_post->work_type_id = $validatedCredentials['work_type_id'];
        $work_post->title = $validatedCredentials['title'];
        $work_post->responsibilities = $validatedCredentials['responsibilities'];
        $work_post->max_applicants = $validatedCredentials['max_applicants'];
        $work_post->qualifications = $validatedCredentials['qualifications'];
        $work_post->start_date = $validatedCredentials['start_date'];
        $work_post->end_date = $validatedCredentials['end_date'];
        $work_post->work_duration = $validatedCredentials['work_duration'];
        // Save changes
        $work_post->save();

        return response()->json(['message' => 'Work Post updated successfully.'], 201);

    
    }

    public function deleteWorkPostById(String $work_post_id) {
          // Get auth user
          $user = Auth::user();
          // Find company
          $company = Company::find($user->id);
          if(!$company) {
              return response()->json(['message' => 'Company not found. Unauthorized.'] , 404);
          }

        // Find work post and delete
        $work_post = WorkPost::find($work_post_id);
        // Delete work_post
        $work_post->delete();

        return response()->json(['message' => 'Work post deleted successfully.'], 201);
  
    }

    /**
     * Summary of addNewWorkPost: A public function that adds a new work post record.
     * @param \App\Http\Requests\CompanyWorkPostRequest $request
     * @return mixed|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function addNewWorkPost(CompanyWorkPostRequest $request) {

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Get auth user
        $user = Auth::user();
        // Find company
        $company = Company::find($user->id);
        if(!$company) {
            return response()->json(['message' => 'Company not found. Unauthorized.'] , 404);
        }

        // Get a company's offices by company_id
        $office = Office::where('company_id', $company->id)->find($validatedCredentials['office_id']);
        if(!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Create new work post
        $work_post = WorkPost::create([
            "office_id" => $office->id,
            "work_type_id" => $validatedCredentials['work_type_id'],
            "responsibilities" => $validatedCredentials['responsibilities'],
            "title" => $validatedCredentials['title'],
            "max_applicants" => $validatedCredentials['max_applicants'],
            "qualifications" => $validatedCredentials['qualifications'],
            "start_date" => $validatedCredentials['start_date'],
            "end_date" => $validatedCredentials['end_date'],
            "work_duration" => $validatedCredentials['work_duration'],

        ]);

        if(!$work_post) {
            return response()->json(['message' => 'Unable to create work post.'], 400);
        }

        return response(['message' => 'Work post successfully created.'], 201);
    
    }

    /**
     * Summary of transform: A privatae function that transforms workPost attributes.
     * @param mixed $workPost
     * @return array
     */
    private function transform($workPost) {
        return [
            "id" => $workPost->id,
            "title" => $workPost->title,
            "work_type_name" => $workPost->workType->name,
            "max_applicants" => $workPost->max_applicants,
            "start_date" => $this->formatDateOnlyDate($workPost->start_date),
            "end_date" => $this->formatDateOnlyDate($workPost->end_date),
            "office_name" => $workPost->office ? $workPost->office->name : "",
            "work_duration" => $workPost->work_duration,
            "work_type_id" => $workPost->work_type_id,
        ];
    }

    /**
     * A public function that gets all work posts
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllWorkPosts() {

        // Get the user
        $user = Auth::user();

        // Get work posts for all offices of the user's company
        $workPosts = WorkPost::with(['office', 'workType'])->whereHas('office', function($query) use ($user) {
            $query->where('company_id', $user['id']);
        })->get();

        // Transform Work Posts
        $transformedWorkPosts = $workPosts->map(function ($workPost) {
            return $this->transform($workPost);
        });

        // Get work_types
        $work_types = WorkType::all();

        // Return
        return response()->json([
            "work_types" => $work_types,
            "initial_work_posts" => $transformedWorkPosts
        ]);
    }

    // Store work posts
    public function store(CompanyWorkPostRequest $request) {
        
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find the office
        $office = Office::find($validatedCredentials['office_id']);


        // Check if office exist
        if(!$office) {
            return response()->json(['message' => 'Office not found.']);
        }

        // Insert new work post
        WorkPost::create([
            "office_id" => $validatedCredentials['office_id'],
            "work_type_id" => $validatedCredentials['work_type_id'],
            "title" => $validatedCredentials['title'],
            "responsibilities" => $validatedCredentials['responsibilities'],
            "qualifications" => $validatedCredentials['qualifications'],
            "start_date" => $validatedCredentials['start_date'],
            "end_date" => $validatedCredentials['end_date'],
            "work_duration" => $validatedCredentials['work_duration'],
        ]);

        // Response
        return response()->json(['message' => 'Work post is created.']);

    }
}
