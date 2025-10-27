<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicantResource;
use App\Models\Application;
use App\Models\Office;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\WorkPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantController extends Controller
{
    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct()
    {
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    public function getAllAInternsOnGoing()
    {

        // Get Supervisor
        $supervisor = Supervisor::where('user_id', $this->user->id)->first();

        // Check if Supervisor exists
        if (!$supervisor) {
            return response()->json(['message' => 'Supervisor not found.'], 404);
        }

        // Check if Supervisor has an office
        $office = $supervisor->company->offices()->first();
        if (!$office) {
            return response()->json(['message' => 'Supervisor does not manage an office.'], 404);
        }

        // Fetch all students (interns) with status_id = 12 through work_posts and applications
        $interns = $office->workPosts()
            ->with(['applications.student.user', 'applications.student.program', 'applications.student.status', 'applications.workPost.office.company'])
            ->get()
            ->flatMap(function ($workPost) {
                return $workPost->applications->filter(function ($application) {
                    // Ensure the student exists and has status_id = 12
                    return $application->student && $application->student->status_id === 12;
                })->map(function ($application) {
                    return [
                        'application' => $application,  // Include the entire application
                        'student' => $application->student, // Include student data
                        'workPost' => $application->workPost, // Include workPost data
                        'company' => $application->workPost->office->company // Include company data
                    ];
                });
            })
            ->unique('student.id'); // Ensure unique students by student ID

        // Transform intern data to include application, student, workPost, and company details
        $transformedInterns = $interns->map(function ($item) {
            $application = $item['application'];
            $student = $item['student'];
            $workPost = $item['workPost'];
            $company = $item['company'];

            return [
                'application_id' => $application->id,
                'id' => $student->id,
                'name' => $student->user ? $this->getFullName(
                    $student->user->first_name ?? "",
                    $student->user->middle_name ?? "",
                    $student->user->last_name ?? ""
                ) : "No name",
                'course' => $student->program->name,
                'status' => $student->status->name,
                'workPost' => $workPost->title, // Add workPost title
                'company' => $company ? $company->name : 'No company assigned', // Add company name (if exists)
            ];
        });

        // Return the list of interns with merged application, student, workPost, and company details
        return response()->json($transformedInterns, 200);
    }


    public function markAsReadyToDeploy(Request $request)
    {
        // Validate the request to ensure `ids` is an array of integers
        $validated = $request->validate([
            'ids' => 'required',
            'ids.*' => 'integer', // Each item in the array should be an integer
        ]);

        // Find records based on the provided IDs
        $students = Student::whereIn('id', $validated['ids']);

        // Update the status_id to 11 for the selected students
        $students->update(['status_id' => 11]); // Mark as Ready to Deploy

        // Return
        return response()->json(['message' => 'Students are ready for deployment', 'data' => $students], 201);
    }




    /**
     * Summary of fetchApplicantFormat: A private function that fetches the format of applicant attributes.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function fetchApplicantFormat()
    {

        return Application::with([
            'student.user',
            'workPost.office.company',
            'student.status',
            'documentSubmissions.documentType',
            'applicationStatus',
            'student.program.college'
        ]);
    }
    /**
     * Summary of getApplicantbyId: A public function that gets the applicant's application by ID.x
     * @param \Illuminate\Http\Request $request
     * @param string $applicant_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getApplicantbyId(Request $request, String $applicant_id)
    {
        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get the authenticated user's (company)
        $authUser = Auth::user();

        // Initialize Variable
        $application = null;

        // ! FOR COMPANY
        if ($authUser->hasRole('company') || $requestedBy === 'company') {

            // Get company ID
            $companyID = $authUser->company->id;

            // Check if the application belongs to the company
            $application = $this->fetchApplicantFormat()->whereHas('workPost.office', function ($query) use ($companyID) {
                $query->where('company_id', $companyID);
            })->find($applicant_id);
        }

        // ! FOR COORDINATOR
        else if ($authUser->hasRole('coordinator') || $requestedBy === 'coordinator') {
            // Get coordinator
            $coordinatorID = $authUser->coordinator->id;

            // Find the application that belongs to student and that student belongs to a coordinator.
            $application = $this->fetchApplicantFormat()->whereHas('student', function ($query) use ($coordinatorID) {
                $query->where('coordinator_id', $coordinatorID);
            })->find($applicant_id);
        }

        // ! FOR OSA
        // TODO: Pending Osa Fetching applicant info
        else if ($authUser->hasRole('osa') || $requestedBy === 'osa') {
            $application = $this->fetchApplicantFormat()->find($applicant_id);
        }

        // Check if application does exist
        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        return $this->jsonResponse(new ApplicantResource($application));
    }

    /**
     * Summary of getAllApplicants: A public function that gets all Applicants
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    /* public function getAllApplicants()
    {

        // Get all offices under the company
        $offices = Office::where('company_id', $this->user->id)->pluck('id');

        // Get all work posts under these offices
        $workPosts = WorkPost::whereIn('office_id', $offices)->pluck('id');

        // Get all applications under these work posts with the desired document type (doc_type_id = 2)
        // and ensure that file_path is not empty
        $applications = Application::with([
            'student.user',
            'workPost.office',
            'student.status',
            'documentSubmissions.documentType'
        ])
            ->whereIn('work_post_id', $workPosts)
            ->whereHas('documentSubmissions', function ($query) {
                $query->where('doc_type_id', 2)
                    ->whereNotNull('file_path') // Ensure file_path is not null
                    ->where('file_path', '!=', ''); // Ensure file_path is not empty
            })
            ->get();

        // Transform Applicants
        $transformApplicants = $applications->map(function ($applicant) {
            return $this->transform($applicant);
        });

        // Return the applications along with the associated student data
        return response()->json($transformApplicants, 200);
    } */

    /**
     * 
     * 
     * 
     * 
     * FETCHING STATUSES
     * 
     * 
     * 
     * 
     */

    /**
     * Summary of fetchApplicants: A private function that gets all applicants.
     * @return \Illuminate\Database\Eloquent\Builder|null
     */
    private function fetchApplicants()
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Initialize query variable
        $query = null;


        // ! FOR COMPANY
        if ($authUser->hasRole('company')) {

            // Get all offices under the company
            $offices = Office::where('company_id', $authUser->id)->pluck('id');

            // Get all work posts under these offices
            $workPosts = WorkPost::whereIn('office_id', $offices)->pluck('id');

            // Query 
            $query = $this->fetchApplicantFormat()
                ->whereIn('work_post_id', $workPosts);

            // ! CHECK IF ID IS THE SAME AS THE COMPANY ID (USTP)
            if (!($authUser->id === $this->COMPANY_ID)) {
                $query->whereHas('documentSubmissions', function ($query) {
                    $query->where('doc_type_id', 2)
                        ->whereNotNull('file_path') // Ensure file_path is not null
                        ->where('file_path', '!=', ''); // Ensure file_path is not empty
                });
            }
        }

        // ! FOR OSA
        else if ($authUser->hasRole('osa')) {

            $query = $this->fetchApplicantFormat();
        }

        // Return query
        return $query;
    }

    /**
     * Summary of fetchApplicantsForCompany: Indicates that it's fetching applicants associated with the company.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /* private function fetchApplicantsForCompany() {

        // Get authenticated user (company)
        $authUser = Auth::user();

        // Get all offices under the company
        $offices = Office::where('company_id', $authUser->id)->pluck('id');

        // Get all work posts under these offices
        $workPosts = WorkPost::whereIn('office_id', $offices)->pluck('id');

        // Query 
        $query = $this->fetchApplicantFormat()
            ->whereIn('work_post_id', $workPosts)
            ->whereHas('documentSubmissions', function ($query) {
                $query->where('doc_type_id', 2)
                    ->whereNotNull('file_path') // Ensure file_path is not null
                    ->where('file_path', '!=', ''); // Ensure file_path is not empty
            });
        
        // Return query
        return $query;

    } */

    /**
     * Summary of getRejectedApplicants: A public function that gets the rejected applicants.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getRejectedApplicants(Request $request) {

         // Define the number of items per page (default to 5)
         $perPage = (int) $request->input('perPage', 5);

         // Get and sanitize the search term
         $searchTerm = $this->sanitizeAndGet($request);

         // Query where applicants status is 3 (Rejected)
        $query = $this->fetchApplicants()->where('application_status_id', 3);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $applications = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $applicationsResources = ApplicantResource::collection($applications);

        // Return resources
        return $applicationsResources;

    }

    /**
     * Summary of getWithdrawnApplicants: A public function that gets all withdrawn applicants
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getWithdrawnApplicants(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Query where applicants status is 5 (Withdrawn)
        $query = $this->fetchApplicants()->where('application_status_id', 5);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $applications = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $applicationsResources = ApplicantResource::collection($applications);

        // Return resources
        return $applicationsResources;
    }

    /**
     * Summary of getAllReadyForDeploymentApplicants: A public function that gets all ready for deployment applicants.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllReadyForDeploymentApplicants(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Query where applicants status is 4 (Approved)
        $query = $this->fetchApplicants()->where('application_status_id', 9);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $applications = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $applicationsResources = ApplicantResource::collection($applications);

        // Return resources
        return $applicationsResources;
    }

    /**
     * Summary of getAllApprovedApplicants: A public function that gets all approved applicants
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllApprovedApplicants(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Query where applicants status is 4 (Approved)
        $query = $this->fetchApplicants()->where('application_status_id', 4);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $applications = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $applicationsResources = ApplicantResource::collection($applications);

        // Return resources
        return $applicationsResources;
    }

    /**
     * Summary of getAllPendingApplicants: A public function that fetches all pending applicants.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllPendingApplicants(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Query where applicants status is 1 (Pending)
        $query = $this->fetchApplicants()->where('application_status_id', 1);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $applications = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $applicationsResources = ApplicantResource::collection($applications);

        // Return resources
        return $applicationsResources;
    }

    /**
     * Summary of getAllApplicants: A public function that gets all applicants.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllApplicants(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Query 
        $query = $this->fetchApplicants();

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $applications = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $applicationsResources = ApplicantResource::collection($applications);

        // Return resources
        return $applicationsResources;
    }
}
