<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentApplicationRequest;
use App\Http\Requests\StudentDocumentSubmissionRequest;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\WorkPost;
use App\Services\ActionLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentApplicationController extends Controller
{

    protected $documentSubmissionController;

     /**
     * File Upload Controller
     */
    private $fileUploadController;

    public function __construct(DocumentSubmissionController $documentSubmissionController, FileUploadController $fileUploadController)
    {
     
        $this->documentSubmissionController = $documentSubmissionController;
        $this->fileUploadController = $fileUploadController;
       
    }


    public function updateUploadedDocument(Request $request, String $application_id, String $document_submission_id)
    {
        // Validate the input request
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,doc|max:10240', // Adjust max size if necessary
        ]);

        // Find the document submission record
        $documentSubmission = DocumentSubmission::where('application_id', $application_id)
            ->where('id', $document_submission_id)
            ->first();

        // Check if the document submission exists
        if (!$documentSubmission) {
            return response()->json(['error' => 'Document submission not found'], 404);
        }

        // Check if a new document is uploaded
        if ($request->hasFile('file')) {

            return Storage::exists("storage/uploads/applications/1/PrCvh02LODEJ8b3Bcgkwr4LPyQBmdNw1vmI6Fv0i.pdf");

            // Delete the old file if it exists
            if ($documentSubmission->file_path && Storage::exists($documentSubmission->file_path)) {
                Storage::delete("public/{$documentSubmission->file_path}");
            }

            // Store the file in the specified directory
            $filePath = $request->file('file')->store("public/uploads/applications/{$application_id}");

            // Replace 'public/' with 'storage/' to create the correct accessible URL
            $filePath = str_replace('public/', 'storage/', $filePath);

            // Define the storage path
            // $folderPath = "applications/{$application_id}"; 

            // Update the document record with the new file path
            $documentSubmission->file_path = $filePath;
            $documentSubmission->save();
        }

        // Return a response
        return response()->json(['message' => 'Document updated successfully'], 200);
    }


    /**
     * Summary of uploadDocument: A public function that uploads a document and send it to the cloud/local storage
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @param string $document_submission_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function uploadDocument(Request $request, String $application_id, String $document_submission_id)
    {

       
        // Check if there is a file
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        // Check if the file is valid
        if (!$request->file('file')->isValid()) {
            return response()->json(['message' => 'Uploaded file is invalid.'], 400);
        }

        // Validate the request to ensure a file is uploaded
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20000', // Adjust file types and size as needed
        ]);

        // Find the application by ID
        $application = Application::findOrFail($application_id);

        // Find the document submission entry for the application and document type
        $documentSubmission = DocumentSubmission::where('application_id', $application->id)->find($document_submission_id);

        // $file = $request->file('file');
        // Store the file in the specified directory
        // $filePath = $file->store("public/uploads/applications/{$application_id}");

        // Replace 'public/' with 'storage/' to create the correct accessible URL
        // $filePath = str_replace('public/', 'storage/', $filePath);

        // Store file Path to bucket
        $filePath = $this->fileUploadController->storeFile($request, $application_id);

        // Define the storage path
        // $folderPath = "applications/{$application_id}";

        // Update the file_path in the document_submission entry
        $documentSubmission->update([
            'file_path' => $filePath,
            'doc_status_id' => 1, // Update status to 'Pending'
            'document_status_id' => 1, // Update status to 'Pending'
        ]);

        // Find Document Submission
        $document = $this->documentSubmissionController->transformStepOneDocuments(DocumentSubmission::with(['documentType', 'status'])->find($documentSubmission->id));
        
        // Return response
        return response()->json([
            'message' => 'Document uploaded successfully.',
            'file_path' => $filePath,
            'data' => $document,
        ], 201);
    }


    /**
     * A public function that gets all application records
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllApplcations()
    {
        // Get authenticated user
        $authUser = Auth::user()->student;

        // Find all applications where student_id exist
        $applications = Application::where("student_id", $authUser['id'])->get();
        // Check if applcations does exist
        if (!$applications) {
            return response()->json(['message' => 'Applications not found.'], 404);
        }

        // Return application records
        return response()->json($applications, 200);
    }

    /**
     * Summary of getApplicationById: A public function that finds and get the application by ID.
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getApplicationById(String $application_id)
    {
        // Get authenticated user
        $authUser = Auth::user()->student;
        
        // Find application where application_id
        $application = Application::with(['documentSubmissions.documentType', 'endorsement', 'workPost'])->where('student_id', $authUser['id'])->find($application_id);
        // Check if application does exist
        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }
       
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

    private function createDocumentSubmissions($application_id)
    {

        $documents = [
            ['doc_type_id' => 2, 'remarks' => 'Incomplete'], // Endorsement Letter
            ['doc_type_id' => 1, 'remarks' => 'Incomplete'], // Cover Letter
            ['doc_type_id' => 3, 'remarks' => 'Incomplete'], // Resume
        ];

        foreach ($documents as $doc) {
            DocumentSubmission::create([
                "application_id" => $application_id,
                "doc_type_id" => $doc['doc_type_id'],
                "doc_status_id" => 7, // Incomplete
                "remarks" => $doc['remarks'],
            ]);
        }
    }

    /**
     * A public function that attempts the student to apply for a job
     */
    public function applyJob(Request $request, String $job_id, ActionLogger $actionLogger)
    {
        // Get the student user
        $user = Auth::user()->student;

        // Check student status if it is rejected - 3
        if ($user->status_id === 3) {
            return response()->json(['message' => 'You are rejected in this job.'], 404);
        }

        // Find the job by ID
        $job = WorkPost::find($job_id);
        if (!$job) {
            $actionLogger->logAction(
                actionType: 'Apply',
                entity: 'Job',
                entityId: $job_id,
                description: "Job application failed. Job not found.",
                status: 'Failed',
                httpCode: "404",
            );
            return response()->json(["message" => "Job not found."], 404);
        }

        // Check if the max applicants limit has been reached
        $currentApplicants = Application::where('work_post_id', $job_id)->count();
        if ($currentApplicants >= $job->max_applicants) {
            $actionLogger->logAction(
                actionType: 'Apply',
                entity: 'Job',
                entityId: $job->id,
                description: "Job application failed. Max applicants limit reached.",
                status: 'Failed',
                httpCode: "400",
            );
            return response()->json(["message" => "The job has already reached its maximum number of applicants."], 400);
        }

        // Check if the student has a pending or applying status
        $hasPendingApplication = Application::where('student_id', $user->id)
            ->where('status_type_id', 1) // Assuming status_type_id 1 is "Pending"
            ->where('created_at', '>=', now()->subDays(3))
            ->exists();

        if ($hasPendingApplication) {
            return response()->json(['error' => 'You have a pending application and cannot apply for another job until 3 days have passed.'], 400);
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
            "id" => $generate_id,
            'work_post_id' => $job->id,
            'student_id' => $user->id,
            'status_type_id' => 1, // Pending
            'remarks' => "Applying.",
            'applied_date' => now(),
            'step' => 1, // Update to step 1
        ]);

        // Check if application is created
        if (!$application) {
            return response()->json(['message' => 'Unable to create application.'], 404);
        }

        /**
         * Creating a record of document submissions required to submit in Step 1
         */
        // Record required documents
        /**
         * Creating a record of document submissions required to submit in Step 1
         */
        // Record required documents
        $this->documentSubmissionController->generateStepOneDocumentSubmissions($application->id);
        // $this->createDocumentSubmissions($application['id']);

        // Update student status
        $user->status_id = 9; // Status: Applying
        $user->save();

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



    // Check if job does exist
    private function findJob(String $job_id)
    {

        // Check if job does exist
        $workPost = WorkPost::find($job_id);
        if (!$workPost) {
            return response()->json(['message' => 'Work Post not found.', 404]);
        }
    }

    // Transform
    private function transform() {}

    // A function that creates a new document submission record for submitting a new document with type
    public function addNewDocument(StudentDocumentSubmissionRequest $request, String $job_id, String $application_id)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Check if work post exists
        $this->findJob($job_id);

        // Check if application exists
        $application = Application::find($application_id);
        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Store the uploaded PDF file
        $file = $request->file('file_path');
        $storedFilePath = $file->store('public/uploads');

        // Format the path as 'storage/uploads/filename.pdf' for database storage
        $formattedPath = str_replace('public/', 'storage/', $storedFilePath);

        // Store new document record
        $documentSubmission = DocumentSubmission::create([
            "application_id" => $application_id,
            "doc_type_id" => $validatedCredentials['doc_type_id'],
            "name" => $validatedCredentials['name'],
            "doc_status_id" => 1, // Set to 1 (Pending)
            "file_path" => $formattedPath, // Use formatted path
        ]);

        // Check if the new record is created
        if (!$documentSubmission) {
            return response()->json(['message' => 'Failed to submit document.'], 500);
        }

        // Return status 201 indicating successful creation
        return response()->json($documentSubmission, 201);
    }


    // A function that creates a new record of application
    public function createNewApplication(StudentApplicationRequest $request, String $job_id)
    {
        // Get the authenticated student's information
        $user = Auth::user()->student;

        // Check if job exists
        $this->findJob($job_id);

        // Create a new application record
        $application = Application::create([
            'work_post_id' => $job_id,
            'student_id' => $user['id'],
            'applied_date' => Carbon::now(),
            'status_type_id' => 1, // Set status to 1 (Pending)
        ]);

        // Check if the application was created successfully
        if (!$application) {
            return response()->json(['message' => 'Application not created.'], 400);
        }

        // Update student's isPending status and last_applied_at fields
        $user->isPending = true;
        $user->last_applied_at = Carbon::now();
        $user->save();

        // Return a success response with status 201
        return response()->json(['message' => 'Application successfully created.'], 201);
    }
}
