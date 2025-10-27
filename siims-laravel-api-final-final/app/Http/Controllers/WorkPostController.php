<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkPostRequest;
use App\Http\Resources\WorkPostResource;
use App\Models\Company;
use App\Models\EndorsementLetterRequest;
use App\Models\Office;
use App\Models\WorkPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WorkPostController extends Controller
{

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * The application controller
     */
    private $applicationController;

    /**
     * Repository
     */
 
    /**
     * DocumentTypeController constructor.
     */
    public function __construct(ApplicationController $applicationController)
    {
        $this->user = Auth::user(); // Initialize the authenticated user
        $this->applicationController = $applicationController;
    }


    public function getWorkPostEndorsement(string $work_post_id) {


        $studentID = $this->user->student->user_id;

        $endorsement = EndorsementLetterRequest::where('requested_by_id', $studentID)->orWhereHas('endorseStudents', function ($query) use ($studentID) {
            $query->where('student_id', $studentID);
        })->where('work_post_id', $work_post_id)->first();

        return $this->jsonResponse($endorsement, 200);

    }

    /**
     * Summary of fetchWorkPostsForStudent: A public function that fecth work posts for student.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function fetchWorkPostsForStudent(Request $request)
    {

        // Get authenticated user
        $authUser = Auth::user()->student;

        /**
         * Check if Student Status ID is 1 (Not Yet Enrolled)
         */
        if ($authUser->student_status_id == 3) {

            // Define the number of items per page (default to 5)
            $perPage = (int) $request->input('perPage', 5);

            // Get and sanitize the search term
            $searchTerm = $this->sanitizeAndGet($request);

            $query = WorkPost::has('office')->with(['workType', 'office', 'office.company']);

            // Apply the search filter if search term is provided
            if (!empty($searchTerm)) {
                $query->where('title', 'LIKE', '%' . strtolower($searchTerm) . '%');
            }

            // Paginate the results
            $workPosts = $query->paginate($perPage);

            // Include both the resource data and the pagination metadata
            return WorkPostResource::collection($workPosts);
        }

        else {

            // * Get Latest Application
            /**
             * Step 1: Find the latest application's 'ID'.
             * Step 2: Find the application by ID (Latest Application ID)
             */
            $latestApplication = $this->applicationController->findApplicationByID($this->applicationController->getLatestApplicationId());

            // Return response 
            return $this->jsonResponse($latestApplication, 200);

        }
    }

    /**
     * Summary of getAllWorkPostsV2: A public function that gets all work posts.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllWorkPostsV2(Request $request) {

        // Get authenticated user
        $authUser = Auth::user();

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Query work posts
        $query = WorkPost::has('office')->with(['workType', 'office', 'office.company'])->whereHas('office', function ($query) use ($authUser) {
            $query->where('company_id', $authUser->id);
        });

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('title', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

         // Sort by the most recent (created_at in descending order)
        $query->orderBy('created_at', 'desc');
            
        // Paginate the results
        $workPosts = $query->paginate($perPage);

        // Transform and collect
        $workPostsResources = WorkPostResource::collection($workPosts);

        // Return 
        return $workPostsResources;
    }   

    /**
     * Summary of getAllWorkPosts: A public function that gets all Transformed Work Posts
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllWorkPosts(Request $request, int $perPage, String $searchTerm)
    {

        // Define the number of items per page (default to 5)
        $perPage = $perPage ?? (int) $request->input('per_page', 5);

        // Get and sanitize the search term
        $searchTerm = $searchTerm ?? $this->sanitizeAndGet($request);

        // Get authenticated user
        $auth = Auth::user();

        // Get work posts
        $workPosts = WorkPost::has('office')->with(['workType', 'office', 'office.company'])->get();

        // Check if Work Post exist
        if (!$workPosts) {
            return response()->json(["message" => "Work Posts not found..."]);
        }

        /**
         * Check if the User has a Role of Student
         */
        if ($auth->hasRole('student')) {
            // Fetch All jobs
            return $this->fetchWorkPostsForStudent($searchTerm, $perPage);
        }

        // Transform Work Post
        $transformedWorkPosts = $workPosts->map(function ($workPost) {
            return $this->transform($workPost);
        });

        // Return response with status 200
        return response()->json($transformedWorkPosts, 200);
    }

    /**
     * Summary of transform: A private function that transforms the Work Post's attributes
     * @param \App\Models\WorkPost $workPost
     * @return array
     */
    private function transform(WorkPost $workPost)
    {

        return [
            "id" => $workPost->id,
            "title" => $workPost->title,
            "responsibilities" => $workPost->responsibilities,
            "qualifications" => $workPost->qualifications,
            "work_duration" => $workPost->work_duration,
            "max_applicants" => $workPost->max_applicants,
            "start_date" => $this->formatDateOnlyDate($workPost->start_date),
            "end_date" => $this->formatDateOnlyDate($workPost->end_date),
            "office" => $workPost->office->name,
            "company" => $workPost->office->company->name,
            "work_post_type" => ucwords($workPost->workType->name),
            "skills" => $workPost->skills ? $workPost->skills->map(function ($skill) {
                return [
                    "id" => $skill->id,
                    "name" => $skill->name,
                ];
            }) : "",
            "is_closed" => Carbon::now()->gt(Carbon::parse($workPost->end_date)),
        ];
    }

    public function getWorkPostDetails(String $work_post_id)
    {

        // Find Work Post by work_post_id
        $workPost = WorkPost::with(['office.company', 'workType', 'skills'])->find($work_post_id);

        // Check if Work Post does exist
        if (!$workPost) {
            return response()->json(['message' => 'Work Post not found.'], 404);
        }

        // Transform Work Post's Attributes
        $transformedWorkPost = $this->transform($workPost);

        // Return response with status 200
        return response()->json($transformedWorkPost, 200);
    }

    // Get all work post
    public function index()
    {

        // Get user
        $user = Auth::user();

        // Get all work posts


        return $user;
    }

    // Store work posts by office id
    public function storeWorkPostByOfficeId(WorkPostRequest $request, String $company_id, String $office_id)
    {

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find Company
        $company = Company::find($company_id);

        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.']);
        }
        // Find Office
        $office = Office::find($office_id);

        // Check if office exist
        if (!$office) {
            return response()->json(['message' => 'Office not found.']);
        }

        // Store work post
        $workPost = WorkPost::create($validatedCredentials);

        if (!$workPost) {
            return response()->json(['message' => 'Cannot create work post.']);
        }
    }

    // Get work posts by office id
    public function getWorkPostsByOfficeId(String $company_id, String $office_id)
    {
        // Get Work Posts by ID
        $workPosts = WorkPost::where('office_id', $office_id)->get();

        // Return
        return response()->json($workPosts, 200);
    }

    // Create work posts by office_id

    // Get all work posts
    /* public function index() {
        return WorkPost::all();
    } */

    // Get all work posts based on office_id
    public function getPostsByOfficeId(String $company_id, String $office_id)
    {
        // Get work posts
        $workPosts = WorkPost::where('office_id', $office_id)->get();

        return $workPosts;
    }

    // Create post
    public function store(WorkPostRequest $request)
    {
        // Get work posts
        $validatedCredentials = $request->validated();

        // Store the work post record
        WorkPost::create($validatedCredentials);

        // Return successful insertion
        return response()->json([
            'success' => 'A work post is created.'
        ]);
    }

    // Get a post
    public function show(String $company_id, String $office_id, String $workPostId)
    {
        // Get post
        $workPost = WorkPost::findOrFail($workPostId);

        return $workPost;
    }

    // Store new work post
    public function create(WorkPostRequest $request)
    {



        // Return successful insertion
        return response()->json([
            'success' => 'The work post is created.'
        ]);
    }
}
