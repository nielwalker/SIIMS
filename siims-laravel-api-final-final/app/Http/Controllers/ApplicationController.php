<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Http\Resources\LatestApplicationResource;
use App\Models\Application;
use App\Models\College;
use App\Models\DocumentSubmission;
use App\Models\EndorsementLetterRequest;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkPost;
use App\Repositories\ApplicationRepositoryInterface;
use App\Repositories\DocumentSubmissionRepositoryInterface;
use App\Repositories\StudentRepositoryInterface;
use App\Services\ApplicationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{


    // Dependency injection for DocumentSubmissionController, Student Controller, and Auth
    protected $documentSubmissionController;
    protected $studentController;

    /**
     * Summary of user
     * @var User|null
     */
    protected $authUser;
    /**
     * File Upload Controller
     */
    private $fileUploadController;


    // Repository
    private $applicationRepositoryInterface;
    private $documentSubmissionRepositoryInterface;
    private $studentRepositoryInterface;

    // Service
    private $applicationService;

    public function __construct(DocumentSubmissionController $documentSubmissionController, StudentController $studentController, FileUploadController $fileUploadController, ApplicationRepositoryInterface $applicationRepositoryInterface, DocumentSubmissionRepositoryInterface $documentSubmissionRepositoryInterface,  StudentRepositoryInterface $studentRepositoryInterface, ApplicationService $applicationService)
    {
        
        $this->authUser = Auth::user();
        $this->documentSubmissionController = $documentSubmissionController;
        $this->studentController = $studentController;
        $this->fileUploadController = $fileUploadController;
        $this->applicationRepositoryInterface = $applicationRepositoryInterface;
        $this->documentSubmissionRepositoryInterface = $documentSubmissionRepositoryInterface;
        $this->studentRepositoryInterface = $studentRepositoryInterface;
        $this->applicationService = $applicationService;
    }


    public function getByID(Request $request, string $applicationID) {
        
        // Fetch parameters
        $requestedBy = $request->query('requestedBy');

        // Ready filters
        $filters = [
            'requestedBy' => $requestedBy,
        ];
        
        // Get Application
        $application = $this->applicationService->getByID($applicationID, $filters);
        
        // Return application
        return response()->json([
            "id" => $application->id,
            "work_post_id" => $application->work_post_id,
            "student_id" => $application->student_id,
            "status_type_id" => $application->status_type_id,
            "application_status_id" => $application->application_status_id,
            "step" => $application->step,
            "remarks" => $application->remarks,
            "job_title" => $application->workPost->title,
            "applied_date" => $application->applied_date,
            "created_at" => $application->created_at,
            "endorsement" => $application->endorsement,
            "updated_at" => $application->updated_at,
            "document_submissions" => $application->document_submissions,
        ], 200);
    }

    /**
     * Summary of findApplicationByID: Retrieve the application by its given ID.
     * 
     * @param string $id The ID of the application to find.
     * @return Application|JsonResponse|array|null The application model, or null if not found.
     */
    public function findApplicationByID(String $id)
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Initialize application variable
        $application = null;

        // ! Check if role is admin or osa
        if ($authUser->hasRole('admin') || $authUser->hasRole('osa')) {

            // Find Application
            $application = Application::find($id);
        }

        // ! Check if role is company
        else if ($authUser->hasRole('company')) {

            // Check if both conditions are met: role is company and application ID matches
            $application = Application::whereHas('workPost.office.company', function ($query) use ($authUser) {
                // Check if the application belongs to the work posts under offices that belong to the company
                $query->where('id', $authUser->company->id);
            })->where('id', $id)->first();
        }

        // ! Check if role is supervisor
        else if ($authUser->hasRole('supervisor')) {

            // Check if both conditions are met: role is supervisor and application ID matches
            $application = Application::whereHas('workPost.office', function ($query) use ($authUser) {

                // Check if the application belongs to the work posts under offices that has assigned to the supervisor
                $query->where('supervisor_id', $authUser->supervisor->id);
            })->where('id', $id)->first();
        }

        // ! Check if role is coordinator
        else if ($authUser->hasRole('coordinator')) {

            // Check if both conditions are met: role is coordinator and application ID matches
            $application = Application::whereHas('student', function ($query) use ($authUser) {

                // Check if the application belongs to the student that is assigned to the coordinator
                $query->where('coordinator_id', $authUser->coordinator->id);
            })->where('id', $id)->first();
        }

        // ! Check if role is student
        else if ($authUser->hasRole('student')) {

            // Check if both conditions are met: role is student and application ID matches
            $application = Application::where('id', $id)
                ->where('student_id', $authUser->student->id) // Add your additional condition here
                ->first();
        }

        // Check if application is not found.
        if (!$application) {
            abort(404, 'Application not found');
        }

        // Return application object
        return $application;
    }

    /**
     * Summary of findApplicationIdById: Retrieve the ID of an application by its given ID.
     * 
     * This function uses findApplicationByID method to fetch an application and extracts its ID property.
     * 
     * @param string $id The ID of the application to find.
     * @return string|null The application's ID, or null if not found.
     */
    public function findApplicationIdById(String $id)
    {

        // Get the application's ID only.
        $applicationID = $this->findApplicationByID($id)->id;

        // Return application ID only
        return $applicationID;
    }

    /**
     * Summary of getLatestApplication: A public function that gets the latest application of student.
     * @return JsonResponse
     */
    public function getLatestApplication() {

        // Get authenticated student
        $student = Auth::user()->student;

        $latestApplication = $student->applications()->with(['workPost.office.company.user'])->latest()->first();

        // Return response, status
        return $this->jsonResponse(new LatestApplicationResource($latestApplication), 200);
    }

    /**
     * Summary of getLatestApplicationId: A public function that gets the latest application.
     * * Note: Only the application 'ID' (latest)  will be returned 
     * @return int
     */
    public function getLatestApplicationId()
    {

        // Get authenticated user (student)
        $student = Auth::user()->student;

        // Get latest application
        $latestApplication = Application::where('student_id', $student->id)->latest()->first();

        return $latestApplication->id;
    }

    /**
     * Summary of addAcceptanceLetter: A public function that adds a new acceptance letter.
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addAcceptanceLetter(Request $request, String $application_id)
    {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'file' => 'required|mimes:pdf|max:10240', // max size 10MB
        ]);

        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        if (!$request->file('file')->isValid()) {
            return response()->json(['message' => 'Uploaded file is invalid.'], 400);
        }

        // Find the application
        $application = Application::find($application_id);
        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        // $file = $request->file('file');
        // Store the file in the specified directory
        // $filePath = $file->store("public/uploads/applications/{$application_id}");

        // Replace 'public/' with 'storage/' to create the correct accessible URL
        // $filePath = str_replace('public/', 'storage/', $filePath);

        // Store the file in the S3 'applications' folder within the bucket
        $filePath = $this->fileUploadController->storeFile($request, $application_id);

        // Create a new document submission
        $document = DocumentSubmission::create([
            "application_id" => $application_id, // Ensure the application ID is linked
            "doc_status_id" => 2, // Approved
            "doc_type_id" => 5, // Acceptance Letter document type
            "name" => "Acceptance Letter",
            "file_path" => $filePath,
            "remarks" => "",
        ]);

        if (!$document) {
            return response()->json(['message' => 'Failed to create document submission.'], 400);
        }

        $this->approveAllStepOneApplication($application_id);

        return response()->json([
            'message' => 'Acceptance Letter uploaded and application approved successfully.',
            'file_path' => $filePath,
        ], 201);
    }

    /**
     * Summary of withdrawApplication: A public function that allows the Student to withdraw from application and sets his/her status.
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function withdrawApplication(String $application_id)
    {

        // Get authenticated user 
        $authUser = Auth::user()->student;

        // Find Student or Fail
        $student = Student::findOrFail($authUser->id);
        // $student->isWithdrawn = true;
        $student->student_status_id = 1;
        $student->save();

        // Set Student Status to Not Yet Applied
        // $this->studentController->updateStudentStatus($student, 8);

        // $student->blocked_until = now()->addDays(3); // Block for 3 days
        // $student->save();

        // Find Application for Fail
        $application = Application::findOrFail($application_id);
        $application->application_status_id = 5;

        // Set Status Type ID to 6
        // $application->status_type_id = 6;
        $application->save();

        // Find the associated endorsement letter
    $endorsementLetterRequest = EndorsementLetterRequest::where('application_id', $application_id)->first();

    if ($endorsementLetterRequest) {
        // Update endorsement letter status
        $endorsementLetterRequest->endorsement_letter_request_status_id  = 4;
        $endorsementLetterRequest->save();
    }

        // Return response and status 201
        return response()->json(['message' => 'You have withdrawn in this application'], 201);
    }

    /**
     * Summary of create: A public function that creates a new application.
     * @param string $work_post_id
     * @return JsonResponse
     */
    public function create(String $work_post_id) {

        // Create application
        $application = $this->applicationRepositoryInterface->create($work_post_id);

        // Create document submission (step 1)
        $this->documentSubmissionRepositoryInterface->generateStepOneDocumentSubmissions($application->id);

        // Update Student to Pending
        $this->studentRepositoryInterface->updateToPending();

        // Update last applied date to now
        $this->studentRepositoryInterface->updateLastAppliedAtToNow();

        // Return status 201 indicating successful creation
        return $this->jsonResponse(['message' => 'Application is created.', 'application_id' => $application->id], 201);

    }

    /**
     * A public function that attempts the student to apply for a job
     */
    public function applyWorkPost(String $work_post_id)
    {
        // Get the student user
        $user = Auth::user();

        /**
         * * Updated at December 24, 2024
         */
        // Check if student_status_id is Not Yet Applied (1)
        if (!($user->student->student_status_id === 1)) {
            return $this->jsonResponse(['message' => 'You are already enrolled in other job.'], 400);
        }

        // Check if there was a previous application and if it was within the last 5 days
        if($user->student->last_applied_at && $user->student->last_applied_at->diffInDays(now()) <= 5) {
            return $this->jsonResponse(['message' => 'You can only apply for a job once every 5 days.'], 400);
        }

        // Find the work post by ID
        $work_post = WorkPost::find($work_post_id);

        // Check if the Job Posting is Expired
        if (now()->greaterThan($work_post->end_date)) {
            return response()->json(['message' => 'This job posting has expired.'], 400);
        }

        // TODO (TBD): Check if the Job Posting is Active
        /* if (!$work_post->is_active) {
            return response()->json(['message' => 'This job posting is no longer active.'], 400);
        } */

        // TODO (TBD): Check for Minimum Requirements
        /* if ($user->gpa < $work_post->minimum_gpa || $user->year_level < $work_post->minimum_year_level) {
            return response()->json(['message' => 'You do not meet the minimum qualifications for this job.'], 400);
        } */

        // TODO (TBD): Check for Blocklist or Banned Status
        /* if ($user->is_blocklisted) {
            return response()->json(['message' => 'You are blocklisted and cannot apply for this job.'], 403);
        } */

        // TODO (TBD): Check for Rejected in this job.
        /* if ($user->status_id === 3) {
            return response()->json(['message' => 'You are rejected in this job.'], 404);
        } */

        /**
         * Prevent Duplicate Applications
         * - Ensure that the student has not already applied to the same job posting.
         */
        $existingApplication = Application::where('work_post_id', $work_post->id)
            ->where('student_id', $user->id)
            ->exists();
        if ($existingApplication) {
            return response()->json(['message' => 'You have already applied to this job.'], 400);
        }

        /**
         * Verify Contact Information
         * - Check if the student's profile has complete and up-to-date contact details (e.g., email, phone number).
         */

        // Verify email
        /* if(!$user->email) {
            return response()->json(['message' => 'Please complete your email with valid email information before applying.'], 400);
        }

        // Verify phone number
        if(!$user->phone_number) {
            return response()->json(['message' => 'Please complete your phone number with valid phone number information before applying.'], 400);
        } */

        // Check if the max applicants limit has been reached
        $currentApplicants = Application::where('work_post_id', $work_post_id)->count();
        if ($currentApplicants >= $work_post->max_applicants) {
            return response()->json(["message" => "The job has already reached its maximum number of applicants."], 400);
        }

        // Check if the student has a pending application
        $pendingApplication = Application::where('student_id', $user->id)
        ->where('application_status_id', 1) // If application_status_id 1 is "Pending"
        ->first();

        // Check if a pending application exists
        if ($pendingApplication) {
            // Check if it is still within 5 days
            $applicationAge = $pendingApplication->created_at->diffInDays(now());
            if ($applicationAge < 5) {
                return response()->json([
                    'error' => 'You have a pending application and cannot apply for another job until 5 days have passed.'
                ], 400);
            } else {
                // Allow reapplication and optionally update the previous application status
                $pendingApplication->application_status_id = 7; // Application_status_id -> 3 is "Expired"
                $pendingApplication->remarks = "Automatically marked as expired after 5 days.";
                $pendingApplication->save();

                // Update Student Status
                $user->student->student_status_id = 1; // Student Status: Not yet applied
                $user->student->save();
            }
        }

        // Generate a random integer key
        $generate_id = rand(1000, 9999); // Random integer between 1000 and 9999

        // Check if the ID already exists in the database
        while (Application::where('id', $generate_id)->exists()) {
            // Generate a new ID if the ID already exists in the database
            $generate_id = rand(1000, 9999);
        }

        // Create a new application record
        $application = Application::create([
            // "id" => $generate_id,
            'work_post_id' => $work_post->id,
            'student_id' => $user->id,
            'status_type_id' => 1, // Pending
            'application_status_id' => 1, // Pending
            'remarks' => "Applying.",
            'applied_date' => now(),
            'step' => 1, // Update to step 1
        ]);

        // Check if application is created
        if (!$application) {
            return response()->json(['message' => 'Unable to create application.'], 404);
        }

        $this->documentSubmissionController->generateStepOneDocumentSubmissions($application->id);
        // $this->createDocumentSubmissions($application['id']);

        // Update student status
        $user->student->status_id = 9; // Status: Applying
        $user->student->student_status_id = 2; // Student Status: Pending Approval
        $user->student->last_applied_at = now(); // Current Date Now
        $user->student->save();

        // Define the path for the new folder
        // Create application folder
        $folderPath = "public/uploads/applications/{$application->id}";
        if (!$this->createApplicationFolder($folderPath)) {
            return response()->json(['message' => 'Unable to create folder for application.'], 400);
        }

        // Return status 201 indicating successful creation
        return response()->json(['message' => 'Application is created.', 'application_id' => $application->id], 201);
    }

    /**
     * Create a folder for the application.
     */
    private function createApplicationFolder($folderPath)
    {
        if (!Storage::exists($folderPath)) {
            return Storage::makeDirectory($folderPath);
        }

        return false; // Folder already exists or failed to create
    }




    /**
     * Summary of approveAllStepTwoApplication: A public function that allows to approve all Documents that are part in Step 2
     * - Update all document submissions that are part in Step 2.
     * - Update Student Status to Ready for Deploymen (11).
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function approveAllStepTwoApplication(String $application_id)
    {

        // Find the Application by application_id, eager load documentSubmissions, documentType, and student
        $documentSubmissions = DocumentSubmission::where('application_id', $application_id)->whereHas('documentType', function ($query) {
            $query->where('document_step_id', 2);
        })->get();

        if (!$documentSubmissions) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Loop through all related document submissions and update their status
        foreach ($documentSubmissions as $documentSubmission) {
            $this->documentSubmissionController->updateDocumentSubmission($documentSubmission, 2); // Passing 2 as the status_id for approval
        }

        // Open Application
        $application = Application::find($application_id);

        // Update Student Status to Ready for Deployment
        $this->studentController->updateStudentStatus(Student::find($application->student_id), 11);

        // Return response with status 201
        return response()->json(['message' => 'Documents updated.'], 201);
    }

    private function rejectAllStepOneApplication(String $application_id) {

        $application = Application::with(['documentSubmissions.documentType.documentStep', 'student'])->find($application_id);

        // Get Document Submissions that are only part of Step One Document
        $documentSubmissions = DocumentSubmission::with(['documentType.documentStep'])->whereHas('documentType.documentStep', function ($query) {
            $query->where('id', 1);
        })->where('application_id', $application_id)->get();

        // Loop through all related document submissions and update their status
        foreach ($documentSubmissions as $documentSubmission) {
            // $this->documentSubmissionController->updateDocumentSubmission($documentSubmission, 2); // Passing 2 as the status_id for approval
            $this->documentSubmissionController->updateDocumentSubmission($documentSubmission, 5); // Passing 5 as the 5 for rejected
        }

        /**
         * - Update Application Status
         * - Update Student Status
         * - 
         */
        // Update Student Status
        $application->student->status_id = 1; // Set to Not Yet Applied
        $application->student->student_status_id = 1; // Set to not yet enrolled
        $application->student->last_applied_at = null; // Set last_applied to null

        /**
         * * New: Updated
         */
        $application->application_status_id  = 3;
        $application->save();

    }

    /**
     * Summary of approveAllStepOneApplication: A private function that approves all Step One Application
     * @param string $application_id
     * @return void
     */
    private function approveAllStepOneApplication(String $application_id)
    {
        $application = Application::with(['documentSubmissions.documentType.documentStep', 'student'])->find($application_id);

        // Get Document Submissions that are only part of Step One Document
        $documentSubmissions = DocumentSubmission::with(['documentType.documentStep'])->whereHas('documentType.documentStep', function ($query) {
            $query->where('id', 1);
        })->where('application_id', $application_id)->get();

        // return $documentSubmission;

        // Loop through all related document submissions and update their status
        foreach ($documentSubmissions as $documentSubmission) {
            // $this->documentSubmissionController->updateDocumentSubmission($documentSubmission, 2); // Passing 2 as the status_id for approval
            $this->documentSubmissionController->updateDocumentSubmission($documentSubmission, 4); // Passing 4 as the 4 for approval
        }

        // Update Student Status
        $application->student->status_id = 10; // Set to Applied

        /**
         * * New: Updated
         */
        $application->application_status_id  = 4;
        $application->save();

        $application->student->student_status_id = 3; // Set to Enrolled
        $application->student->save();

        // Check if Step 2 has already been generated for this student
        $stepTwoDocumentSubmissionExists = DocumentSubmission::where('application_id', $application->id)
            ->whereHas('documentType.documentStep', function ($query) {
                $query->where('id', 2); // Check if any Step 2 documents already exist
            })
            ->exists(); // Returns true if there are any Step 2 submissions

        // If no Step 2 document submission exists, generate Step 2 documents
        if (!$stepTwoDocumentSubmissionExists) {
            $this->documentSubmissionController->generateStepTwoDocumentSubmissions($application->id);
        }
    }

    public function markRejectApplication(String $application_id) {

        // Find application
        $this->rejectAllStepOneApplication($application_id);

        // Return response with status 201
        return $this->jsonResponse([
            'message' => 'Application Rejected!'
        ], 201);

    }

    /**
     * Summary of markApproveApplication: A public function that approves an application.
     * - Approves the Documents belong to the Step One ID
     * - Sets the Status of Application
     * - Sets the Status of Student
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function markApproveApplication(String $application_id)
    {

        // Find Application
        $this->approveAllStepOneApplication($application_id);

        // Return response with status 201
        return $this->jsonResponse([
            'message' => 'Application Approved!'
        ], 201);
       
    }

    // Get all applications by Student ID
    public function getApplicationsByStudentId(String $student_id)
    {
        return Application::where('student_id', $student_id);
    }

    // Get all applications
    public function index()
    {
        return Application::all();
    }
}
