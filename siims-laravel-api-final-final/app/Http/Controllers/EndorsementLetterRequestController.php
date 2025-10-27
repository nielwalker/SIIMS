<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndorsementLetter;
use App\Http\Requests\EndorsementLetterFileRequest;
use App\Http\Requests\EndorsementLetterRequests;
use App\Http\Requests\ManualEndorsementLetterRequests;
use App\Http\Resources\EndorsementLetterRequestResource;
use App\Models\Application;
use App\Models\College;
use App\Models\EndorsementLetterRequest;
use App\Models\EndorseStudent;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkPost;
use App\Services\EndorsementLetterRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EndorsementLetterRequestController extends Controller
{


    /**
     * Summary of Authenticated user
     * @var User|null
     */
    private $user;


    // Services
    private $endorsementLetterRequestService;

    /**
     * CloudController constructor.
     */
    private $cloudController;
    public function __construct(CloudController $cloudController, EndorsementLetterRequestService $endorsementLetterRequestService)
    {
        $this->user = Auth::user(); // Initialize the authenticated user
        $this->cloudController = $cloudController;
        $this->endorsementLetterRequestService = $endorsementLetterRequestService;
    }


    public function markAsApproved(Request $request)
    {
        // Validate the request to ensure `ids` is an array of integers
        $validated = $request->validate([
            'ids' => 'required',
            'ids.*' => 'string', // Each item in the array should be an integer
        ]);

        // Find records based on the provided IDs
        $endorsementLetters = EndorsementLetterRequest::whereIn('id', $validated['ids']);

        // Soft delete the endorsement letter records (archiving)
        if (!$endorsementLetters->exists()) {
            // Return error if no records found
            return response()->json(['error' => 'No endorsement letter found with the provided IDs'], 404);
        }

        // Update the status_id to 2 for the selected endorsement letters
        $endorsementLetters->update(['status_id' => 2]); // Mark as Approved
        $endorsementLetters->update(['endorsement_letter_request_status_id' => 3]); // Mark as Approved
        // $endorsementLetters->save();

        // Get all endorsement letter request
        $endorsementLetters = $this->getAllTransformedWaitingForApprovalEndorsementLetterRequests();

        // Return
        return response()->json(['message' => 'Letters marked successfully', 'data' => $endorsementLetters], 201);

        // Returning those updated

        // Get all endorsement letter requests
        /* $fetchEndorsementLetters = $this->fetchEndorsementLetterRequests()->where('endorsement_letter_request_status_id', 2)->get(); // Pending Approval

      
        // Returning those updated
        return EndorsementLetterRequestResource::collection($fetchEndorsementLetters); */
    }

    /**
     * Summary of deanSubmitEndorsementLetter: A public function that allows the Dean to submit back an endorsement letter.
     * @param \Illuminate\Http\Request $request
     * @param string $endorsement_request_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function deanSubmitEndorsementLetter(Request $request, String $endorsement_request_id)
    {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'pdf_file' => 'required|mimes:pdf|max:10240', // max size 10MB (adjust as needed)
        ]);

        // Find the existing endorsement request by its ID
        $endorsementRequest = EndorsementLetterRequest::find($endorsement_request_id);

        // Check if the endorsement request exists
        if (!$endorsementRequest) {
            return response()->json(['message' => 'Endorsement request not found'], 404);
        }

        // Check if a PDF file has been uploaded
        if ($request->hasFile('pdf_file')) {

            // Store the uploaded PDF file in the designated folder
            /* $pdf_path = $request->file('pdf_file')->store('public/uploads/endorsement_letters');

            // Replace 'public/' with 'storage/' to create the correct accessible URL
            $pdf_path = str_replace('public/', 'storage/', $pdf_path); */

            $pdf_path = $this->cloudController->storeFile($request);

            // Update the endorsement request with the PDF file path
            $endorsementRequest->endorsement_file = $pdf_path;
            // Update the endorsement request status to 13
            // $endorsementRequest->status_id = 13; // Waiting for approval
            $endorsementRequest->save();
        } else {
            return response()->json(['message' => 'No PDF file uploaded'], 400);
        }

        // Get transformed Endorsement Letter Requests
        /* $transformedEndorsementLetterRequests = $this->getAllTransformedWaitingForApprovalEndorsementLetterRequests();
       

        // Return success response with the file path or any other relevant info
        return response()->json([
            'message' => 'Endorsement letter uploaded successfully',
            'data' => $transformedEndorsementLetterRequests
        ], 201);
 */
        // Find Transformed Endorsement Letter Requests
        $endorsementLetterRequests = $this->fetchEndorsementLetterRequests()->find($endorsementRequest->id);
        return $this->jsonResponse([
            'message' => "File Uploaded",
            'data' => new EndorsementLetterRequestResource($endorsementLetterRequests)
        ], 201);
    }

    /**
     * Summary of restoreByID: A public function that restores a record.
     * @param string $endorsment_request_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreByID(String $endorsment_request_id) {

        // Restore record by ID.
        $this->endorsementLetterRequestService->restoreByID($endorsment_request_id);

        // Return
        return $this->jsonResponse(['message' => "Endorsement letter request is restored."], 201);
    }

    /**
     * Summary of delete: A public function that deletes a endorsement letter request record by ID.
     * @param string $endorsment_request_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(String $endorsment_request_id) {

        // Delete Endorsement letter request
        $this->endorsementLetterRequestService->delete($endorsment_request_id);

        // Return
        return $this->jsonResponse(['message' => "Endorsement letter request is deleted."], 201);

    }

    /**
     * Summary of addEndorsementLetter: A public function that adds a new Endorsement Letter File.
     * @param \App\Http\Requests\EndorsementLetterFileRequest $request
     * @param string $endorsement_request_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addEndorsementLetter(Request $request, String $endorsement_request_id)
    {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'pdf_file' => 'required|mimes:pdf|max:10240', // max size 10MB (adjust as needed)
        ]);

        // Find the existing endorsement request by its ID
        $endorsementRequest = EndorsementLetterRequest::find($endorsement_request_id);

        // Check if the endorsement request exists
        if (!$endorsementRequest) {
            return response()->json(['message' => 'Endorsement request not found'], 404);
        }

        // Check if a PDF file has been uploaded
        if ($request->hasFile('pdf_file')) {

            // Store the uploaded PDF file in the designated folder
            /* $pdf_path = $request->file('pdf_file')->store('public/uploads/endorsement_letters');

            // Replace 'public/' with 'storage/' to create the correct accessible URL
            $pdf_path = str_replace('public/', 'storage/', $pdf_path); */

            $pdf_path = $this->cloudController->storeFile($request);

            // Update the endorsement request with the PDF file path
            $endorsementRequest->endorsement_file = $pdf_path;
            // Update the endorsement request status to 13
            $endorsementRequest->status_id = 13; // Waiting for approval
            $endorsementRequest->endorsement_letter_request_status_id = 2; // Pending Approval.
            $endorsementRequest->save();
        } else {
            return response()->json(['message' => 'No PDF file uploaded'], 400);
        }

        // Return success response with the file path or any other relevant info
        return response()->json([
            'message' => 'Endorsement letter uploaded successfully',
            'file_path' => $pdf_path
        ], 201);
    }

    /**
     * Summary of fetchEndorsementLetterRequests: A private function that fetch query endorsement letter requests
     * @return mixed|\Illuminate\Database\Eloquent\Builder|\Illuminate\Http\JsonResponse
     */
    private function fetchEndorsementLetterRequests()
    {


        /**
         * @var User|null
         */
        $authUser = Auth::user();

        // * Initialize query variable
        $query = null;

        // ! FOR DEAN
        if ($authUser->hasRole('dean')) {

            // Find the college by the current user (dean)
            $college = College::where('dean_id', $authUser->id)->first();

            if (!$college) {
                return response()->json(['message' => 'College not found or not assigned to this dean.'], 403);
            }

            // Find all endorsement letter requests
            $query = EndorsementLetterRequest::whereHas('student.program.college', function ($query) use ($college) {
                $query->where('id', $college->id);
            })->with(['student.user', 'endorsementLetterRequestStatus', 'student.program.chairperson']);
        }

        // ! FOR CHAIRPERSON
        else {
            // Find the program chaired by the current user
            $program = Program::where('chairperson_id', $this->user->id)->first();

            if (!$program) {
                return response()->json(['message' => 'Program not found or not assigned to this chairperson.'], 403);
            }

            // Get all students in the program
            $students = Student::where('program_id', $program->id)->pluck('user_id');

            // Fetch endorsement letter requests for the students in the program
            $query = EndorsementLetterRequest::whereIn('requested_by_id', $students)
                ->with(['student', 'status', 'endorseStudents.student.user', 'application.workPost', 'endorsementLetterRequestStatus'])->withCount(['endorseStudents']);
        }

        // Return Query
        return $query;
    }

    /**
     * Summary of getAllTransformedWaitingForApprovalEndorsementLetterRequests: A private function that gets all transformed endorsement letter requests (Ony those status that meets in the dean.)
     * @return mixed|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    private function getAllTransformedWaitingForApprovalEndorsementLetterRequests()
    {
        // Find Dean by college
        $college = College::where('dean_id', $this->user->id)->first();

        // Check if college does exist
        if (!$college) {
            return response()->json(['message' => 'College not found.']);
        }

        // Find all endorsement letter requests that has status of 2, 3 and 13
        $endorsementLetterRequests = EndorsementLetterRequest::with(['application.student.program.college.dean', 'status', 'application.student.user'])->whereIn('status_id', [2, 13, 3])->whereHas('application.student.program.college', function ($query) use ($college) {
            $query->where('id', $college->id);
        })->where('status_id', 13)->get();

        // Check if endorsement letter requests does exist
        if (!$endorsementLetterRequests) {
            return response()->json(['message' => 'No endorsement letter requests found.'], 404);
        }

        // Transform Endorsement Letter Requests
        $transformedEndorsementLetterRequests = $endorsementLetterRequests->map(function ($request) {
            return [
                "id" => $request->id,
                "name" => $request->student->user ? $this->getFullName(
                    $request->student->user->first_name ?? "",
                    $request->student->user->middle_name ?? "",
                    $request->student->user->last_name ?? "",
                ) : "No Name",
                "chairperson" => $request->student->program->college->dean ? $this->getFullName(
                    $request->student->program->college->dean->first_name ?? "",
                    $request->student->program->college->dean->middle_name ?? "",
                    $request->student->program->college->dean->last_name ?? "",
                ) : "",
                "program" => $request->student->program->name,
                "file_path" => $request->endorsement_file,
                "status" => $request->status->name,
                "update_endorsement" => true,
            ];
        });

        // Return transformed Endorsement Letter Requests
        return $transformedEndorsementLetterRequests;
    }

    /**
     * Summary of getAllWaitingForApprovalEndorsementLetterRequests: A public function that gets all endorsement letter requests that is waiting for approval.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllWaitingForApprovalEndorsementLetterRequests()
    {

        $transformedEndorsementLetterRequests = $this->getAllTransformedWaitingForApprovalEndorsementLetterRequests();

        // Return response with status 200
        return response()->json($transformedEndorsementLetterRequests, 200);
    }

    /**
     * Summary of markAsApproval: A public function that marks waiting for approval (From Chairperson to Dean)
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function markAsApproval(Request $request)
    {

        // Validate the request to ensure `ids` is an array of integers
        $validated = $request->validate([
            'ids' => 'required',
            'ids.*' => 'string', // Each item in the array should be an integer
        ]);

        // Find records based on the provided IDs
        $endorsementLetters = EndorsementLetterRequest::whereIn('id', $validated['ids']);

        // Soft delete the endorsement letter records (archiving)
        if (!$endorsementLetters->exists()) {
            // Return error if no records found
            return response()->json(['error' => 'No endorsement letter found with the provided IDs'], 404);
        }


        // Update the status_id to 13 for the selected endorsement letters
        $endorsementLetters->update(['status_id' => 13]);
        $endorsementLetters->update(['status_id' => 2]); // Pending Approval

        // Get all endorsement letter request
        $endorsementLetters = $this->getAllTransformedEndorsementLetterRequests();

        // Return
        return response()->json(['message' => 'Letters marked successfully', 'data' => $endorsementLetters], 201);
    }


    /**
     * Summary of manualAddNewRequest: A public function that handles add new request (for manual)
     * @param \App\Http\Requests\ManualEndorsementLetterRequests $manualEndorsementLetterRequests
     * @return void
     */
    public function manualAddNewRequest(ManualEndorsementLetterRequests $manualEndorsementLetterRequests)
    {

        // Get validated data
        $validated = $manualEndorsementLetterRequests->validated();

        // Add filters
        $filters = [
            "type" => 'manual'
        ];

        // Create
        $this->endorsementLetterRequestService->create($validated, $filters);

        // Return response with status 201
        return response()->json(['message' => 'Request sent.'], 201);
    }

    /**
     * Summary of create: A function that adds a new Endorsement Requests.
     * @param \App\Http\Requests\EndorsementLetterRequests $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function create(EndorsementLetterRequests $request)
    {
        // Get validated Credentials
        $validated = $request->validated();

        // Add filters
        $filters = [
            "type" => 'system'
        ];

        // Create new record
        $endorsementLetterRequest = $this->endorsementLetterRequestService->create($validated, $filters);

        // Return
        return response()->json(['message' => 'Request sent.', 'data' => $endorsementLetterRequest], 201);
    }

    /**
     * Summary of getEndorsementLetterRequest: A public function that gets an Endorsement Letter Request detail by ID>
     * @param mixed $endorsement_request_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getEndorsementLetterRequest($endorsement_request_id)
    {

        // Find Chairperson in program
        $program = Program::where('chairperson_id', $this->user->id)->with(['college'])->first();

        // Program not found
        if (!$program) {
            return response()->json(['message' => 'Program not found.'], 404);
        }

        // Find Students that belongs to a program
        $studentIds = $program->students->pluck('id'); // Pluck the IDs of students

        // Retrieve the endorsement letter request within the program's students
        $endorsementLetterRequest = EndorsementLetterRequest::with([
            'application.workPost.office.company.user',
            'student.user',
            'endorseStudents.student.user',
            'student.program.college',
            'student.coordinator.user',
            'student.program.chairperson',
            'student.program.college.dean'
        ])
            ->whereIn('requested_by_id', $studentIds)
            ->find($endorsement_request_id);

        // Transform Student
        $transformedEndorsementLetterRequest = $this->transform($endorsementLetterRequest, 2);

        // Return the endorsement letter request
        return response()->json($transformedEndorsementLetterRequest, 200);
    }

    // Transforms the endorsement letter requests
    private function transform($endorsement_letter_request, int $option = 1)
    {
        if ($option === 1) {
            return [
                "id" => $endorsement_letter_request->id,
                "student_id" => $endorsement_letter_request->student->id,
                "first_name" => $endorsement_letter_request->student->user->first_name,
                "middle_name" => $endorsement_letter_request->student->user->middle_name,
                "last_name" => $endorsement_letter_request->student->user->last_name,
                "email" => $endorsement_letter_request->student->user->email,
                "phone_number" => $endorsement_letter_request->student->user->phone_number,
                "description" => $endorsement_letter_request->description,
                "file_path" => $endorsement_letter_request->endorsement_file,
                "remarks" => $endorsement_letter_request->remarks,
                "created_at" => $this->formatDateOnlyDate($endorsement_letter_request->created_at),
                "updated_at" => $this->formatDateOnlyDate($endorsement_letter_request->updated_at),
            ];
        } else if ($option === 2) {
            return [
                "id" => $endorsement_letter_request->id,
                'job_title' => $endorsement_letter_request->application->workPost->title,
                'company' => $endorsement_letter_request->application->workPost->office->company->name,
                'office' => $endorsement_letter_request->application->workPost->office->name,
                "description" => $endorsement_letter_request->description,
                "file_path" => $endorsement_letter_request->endorsement_file,
                "remarks" => $endorsement_letter_request->remarks,
                "created_at" => $endorsement_letter_request->created_at,
                "updated_at" => $this->formatDateOnlyDate($endorsement_letter_request->updated_at),
                "requested_by_full_name" => trim(
                    $endorsement_letter_request->student->user->first_name . ' ' .
                        ($endorsement_letter_request->student->user->middle_name ? $endorsement_letter_request->student->user->middle_name . ' ' : '') .
                        $endorsement_letter_request->student->user->last_name,
                ),
                "student" => $endorsement_letter_request->student,
                "endorse_students" => $endorsement_letter_request->endorseStudents->map(function ($endorse_student) {
                    $user = $endorse_student->student->user;
                    return [
                        "student_id" => $endorse_student->student->id,
                        "full_name" => trim(
                            $user->first_name . ' ' .
                                ($user->middle_name ? $user->middle_name . ' ' : '') .
                                $user->last_name
                        ),
                        "email" => $user->email,
                        "phone_number" => $user->phone_number,
                    ];
                }),

                "company_details" => $endorsement_letter_request->application->workPost->office->company,
            ];
        } else if ($option === 3) {
            return [
                "id" => $endorsement_letter_request->id,
                "name" => $endorsement_letter_request->student->user ? $this->getFullName(
                    $endorsement_letter_request->student->user->first_name ?? "",
                    $endorsement_letter_request->student->user->middle_name ?? "",
                    $endorsement_letter_request->student->user->last_name ?? ""
                ) : "", // Return empty string if 'user' is null
                "description" => $endorsement_letter_request->description,
                "job_title" => $endorsement_letter_request->workPost->title,
                "endorse_students_count" => $endorsement_letter_request->endorse_students_count,
                "file_path" => $endorsement_letter_request->endorsement_file,
                "letter_status_id" => $endorsement_letter_request->endorsementLetterRequestStatus->id,
                "letter_status_name" => $endorsement_letter_request->endorsementLetterRequestStatus->name,
                "status" => $endorsement_letter_request->status->name,
                "remarks" => $endorsement_letter_request->remarks,
            ];
        }
    }

    /**
     * Summary of getAllTransformedEndorsementLetterRequests: A private function that gets all endorsement letter requests (transformed)
     * @return mixed|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    private function getAllTransformedEndorsementLetterRequests()
    {
        // Find the program chaired by the current user
        $program = Program::where('chairperson_id', $this->user->id)->first();

        if (!$program) {
            return response()->json(['message' => 'Program not found or not assigned to this chairperson.'], 404);
        }

        // Get all students in the program
        $students = Student::where('program_id', $program->id)->pluck('user_id');

        // Fetch endorsement letter requests for the students in the program
        $requests = EndorsementLetterRequest::whereIn('requested_by_id', $students)
            ->with(['student', 'status', 'endorseStudents.student.user', 'application.workPost', 'endorsementLetterRequestStatus'])->withCount(['endorseStudents'])->get();

        $transformedRequests = $requests->map(function ($request) {
            return $this->transform($request, 3);
        });

        // Return transformed requests
        return $transformedRequests;
    }

    /**
     * Summary of getAllEndorsementLetterRequestsApproved: A public function that gets all pending endorsement letter requests.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllEndorsementLetterRequestsApproved(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        $query = $this->fetchEndorsementLetterRequests()->where('endorsement_letter_request_status_id', 3); // Approved

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $letterRequests = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $letterRequestsResources = EndorsementLetterRequestResource::collection($letterRequests);

        // Return resource
        return $letterRequestsResources;
    }

    /**
     * Summary of getAllEndorsementLetterRequestsWithdrawn: A public function that gets all withdrawn endorsement letter
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllEndorsementLetterRequestsWithdrawn(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        $query = $this->fetchEndorsementLetterRequests()->where('endorsement_letter_request_status_id', 4); // Withdrawn
        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $letterRequests = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $letterRequestsResources = EndorsementLetterRequestResource::collection($letterRequests);

        // Return resource
        return $letterRequestsResources;
    }

    /**
     * Summary of getAllEndorsementLetterRequestsPendingApproval: A public function that gets all pending endorsement letter requests.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllEndorsementLetterRequestsPendingApproval(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        $query = $this->fetchEndorsementLetterRequests()->where('endorsement_letter_request_status_id', 2); // Pending Approval

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $letterRequests = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $letterRequestsResources = EndorsementLetterRequestResource::collection($letterRequests);

        // Return resource
        return $letterRequestsResources;
    }

    /**
     * Summary of getAllEndorsementLetterRequestsPending: A public function that gets all pending endorsement letter requests.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllEndorsementLetterRequestsPending(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        $query = $this->fetchEndorsementLetterRequests()->where('endorsement_letter_request_status_id', 1); // Pending

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $letterRequests = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $letterRequestsResources = EndorsementLetterRequestResource::collection($letterRequests);

        // Return resource
        return $letterRequestsResources;
    }

    /**
     * Summary of get: Retrieve all endorsement letter requests for the program chaired by the user.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {

        // Added filters
        $filters = [
            'perPage' => (int) $request->input('perPage', 5),
            'searchTerm' => $this->sanitizeAndGet($request),
            'requestedBy' => $request->query('requestedBy'),
            'status' => $request->query('status'),
            'date' => $request->query('date'),
        ];

        // Endorsement letter request
        $endorsementLetterRequests = $this->endorsementLetterRequestService->get($filters);

        // Transform the paginated data into a resource collection
        $letterRequestsResources = EndorsementLetterRequestResource::collection($endorsementLetterRequests);

        // Return resource
        return $letterRequestsResources;
    }
}
