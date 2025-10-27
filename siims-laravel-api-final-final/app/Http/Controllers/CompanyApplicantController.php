<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyApplicantRequest;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\Office;
use App\Models\Status;
use App\Models\Student;
use App\Models\WorkPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyApplicantController extends Controller
{

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;
    protected $documentSubmissionController;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct(DocumentSubmissionController $documentSubmissionController) {
        $this->user = Auth::user(); // Initialize the authenticated user
        $this->documentSubmissionController = $documentSubmissionController;
    }

    /**
     * Update the document submission.
     */
    private function updateDocument(DocumentSubmission $document, array $validated)
    {
        $document->remarks = $validated['remarks'];
        $document->doc_status_id = $validated['status_id'];
        $document->save();
    }


    /**
     * Handle rejection logic.
     */
    private function handleRejection($documents, String $application_id)
    {
        $anyRejected = $documents->every(function ($doc) {
            return $doc->doc_status_id == 3; // Rejected
        });

        if ($anyRejected) {
            $application = Application::find($application_id);
            if ($application) {
                $application->status_type_id = 3; // Rejected
                $application->save();
            }

            $this->updateStudentStatus($application, 3); // Set to rejected
            return true;
        }

        return false;
    }

    /**
     * Update the student's status based on the application status.
     */
    private function updateStudentStatus(?Application $application, int $status)
    {
        if ($application && $application->student_id) {
            $student = Student::find($application->student_id);
            if ($student && $status === 10) { // Applied Status Number

                $student->status_id = $status;
                $student->save();

                // After updating the student's status the syste will generae other documents base on the student handbook
                /**
                 * - Medical Certificate            - 4
                 * - Transcript of Records          - 10
                 * - Certificate of Enrollment      - 11
                 * - Certificate of Registration    - 12
                 */
                DocumentSubmission::create([
                    "application_id" => $application->id,
                    "doc_type_id" => 4,
                    "doc_status_id" => 7,
                    "remarks" => "",
                    "name" => "Medical Certificate",
                ]);

                DocumentSubmission::create([
                    "application_id" => $application->id,
                    "doc_type_id" => 10,
                    "doc_status_id" => 7,
                    "remarks" => "",
                    "name" => "Transcript of Records",
                ]);
                DocumentSubmission::create([
                    "application_id" => $application->id,
                    "doc_type_id" => 11,
                    "doc_status_id" => 7,
                    "remarks" => "",
                    "name" => "Certificate of Enrollment",
                ]);
                DocumentSubmission::create([
                    "application_id" => $application->id,
                    "doc_type_id" => 12,
                    "doc_status_id" => 7,
                    "remarks" => "",
                    "name" => "Certificate of Registration",
                ]);
            }

            /**
             * If not applied yet then update status
             */
            // If not then change status
            $student->status_id = $status;
            $student->save();
        }
    }

    /**
     * Handle approval logic.
     */
    private function handleApproval($documents, String $application_id)
    {
        $allApproved = $documents->every(function ($doc) {

            return $doc->doc_status_id == 2; // Approved
        });

        if ($allApproved) {
            $application = Application::find($application_id);
            if ($application) {
                $application->status_type_id = 2; // Approved
                $application->save();
            }

            $this->updateStudentStatus($application, 10);
            return true;
        }

        return false;
    }

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

        $file = $request->file('file');
        // Store the file in the specified directory
        $filePath = $file->store("public/uploads/applications/{$application_id}");

        // Replace 'public/' with 'storage/' to create the correct accessible URL
        $filePath = str_replace('public/', 'storage/', $filePath);

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

        // Update all related document submissions for the application to 'Approved'
        DocumentSubmission::where('application_id', $application_id)
            ->whereIn('doc_type_id', [1, 2, 3]) // Specify IDs for required documents
            ->update(['doc_status_id' => 2]);

        // Update application status to 'Approved'
        $application->status_type_id = 2; // Approved status
        $application->step = 2;
        $application->save();

        // Update student status to 'Approved' (status ID 2)
        $this->updateStudentStatus($application, 10);

        return response()->json([
            'message' => 'Acceptance Letter uploaded and application approved successfully.',
            'file_path' => $filePath,
        ], 200);
    }

    public function markApplicationApprove(String $application_id)
    {

        /**
         * Mark application to Approve
         */

        // Find application
        $application = Application::with(['student', 'documentSubmissions'])->find($application_id);


        /**
         * Update all document submission to approve
         */
        // Retrieve all document submissions for the application
        $documents = DocumentSubmission::where('application_id', $application_id)
            ->whereIn('doc_type_id', [1, 2, 3])
            ->get();

        // Default Approval Validated
        $approvalValues = [
            "remarks" => "Approved Appplication",
            "status_id" => 2,
        ];

        // Loop Through Have All Document Submission Be Approve
        foreach ($documents as $document) {
            // Update the specific document
            $this->updateDocument($document, $approvalValues);
        }


        if ($this->handleApproval($documents, $application_id)) {

            /**
             * Update application step here
             */
            $application = Application::find($application_id);
            $application->step = 2;
            $application->save();

            return response()->json(['message' => 'Application Approved!']);
        }

        return response()->json(['message' => 'It works but something is wrong!'], 400);
    }

    public function updateApplicantApplicationById(CompanyApplicantRequest $request, String $application_id, String $document_id)
    {
        // Get validated data
        $validated = $request->validated();

        // Find the specific document submission by application_id and document_id
        $document = DocumentSubmission::where('application_id', $application_id)
            ->whereIn('doc_type_id', values: [1, 2, 3])
            ->find($document_id);

        // Check if the document exists
        if (!$document) {
            return response()->json(['message' => 'Document not found or does not match the criteria'], 404);
        }

        // Update the specific document
        $this->updateDocument($document, $validated);

        // Retrieve all document submissions for the application
        $documents = DocumentSubmission::where('application_id', $application_id)
            ->whereIn('doc_type_id', [1, 2, 3])
            ->get();

        // Handle rejection, approval, or mixed statuses
        if ($this->handleRejection($documents, $application_id)) {
            return response()->json(['message' => 'Application Rejected due to a rejected document.']);
        }

        if ($this->handleApproval($documents, $application_id)) {

            /**
             * Update application step here
             */
            $application = Application::find($application_id);
            $application->step = 2;
            $application->save();

            return response()->json(['message' => 'Application Approved!']);
        }

        // Default response if no specific condition is met
        return response()->json(['message' => 'Document status updated successfully!']);
    }




    private function transform($applicant, int $option = 1)
    {

        if ($option == 1) {
            return [
                "id" => $applicant->id,
                "job_title" => $applicant->workPost->title,
                "student_id" => $applicant->student->id,
                "first_name" => $applicant->student->user->first_name,
                "middle_name" => $applicant->student->user->middle_name,
                "last_name" => $applicant->student->user->last_name,
                "email" => $applicant->student->user->email,
                "phone_number" => $applicant->student->user->phone_number,
                "documents" => $applicant->documentSubmissions ? $applicant->documentSubmissions->map(function ($document) {
                    return [
                        "file_type" => $document->documentType->name,
                        "file_path" => $document->file_path,
                    ];
                }) : "",
                "status" => $applicant->student->status->name,
           
            ];
        }

        return [
            "id" => $applicant->id,
            "student_id" => $applicant->student_id,
            "first_name" => $applicant->student->user->first_name,
            "middle_name" => $applicant->student->user->middle_name,
            "last_name" => $applicant->student->user->last_name,
            "email" => $applicant->student->user->email,
            "phone_number" => $applicant->student->user->phone_number,
            "date_of_birth" => $this->formatDateOnlyDate($applicant->student->date_of_birth),
            "program" => $applicant->student->program->name,
            "college" => $applicant->student->program->college->name,
            "status" => $applicant->student->status->name,
            "documents" => $applicant->documentSubmissions->map(function ($document) {
                return [
                    "id" => $document->id,
                    "doc_type_id" => $document->documentType->id,
                    "file_path" => $document->file_path,
                    "document_type" => $document->documentType->name,
                    "name" => $document->name,
                    "remarks" => $document->remarks,
                    "status_id" => $document->status->id,
                    "status" => $document->status->name,
                    "created_at" => $document->created_at,
                    "updated_at" => $document->updated_at,
                ];
            }),
            "office" => $applicant->workPost->office,

        ];
    }

    public function getAllApplicants()
    {
        // Get the authenticated user's company
        $auth = Auth::user()->company;

        // Ensure the authenticated user belongs to a company
        if (!$auth) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Get all offices under the company
        $offices = Office::where('company_id', $auth->id)->pluck('id');

        // Get all work posts under these offices
        $workPosts = WorkPost::whereIn('office_id', $offices)->pluck('id');

        // Get all applications under these work posts and include related student and user data
        $applications = Application::with(['student.user', 'workPost.office', 'student.status', 'documentSubmissions.documentType'])
            ->whereIn('work_post_id', $workPosts)
            ->get();

        // Check if applications exist
        if ($applications->isEmpty()) {
            return response()->json(['message' => 'No applications found for this company.',], 404);
        }

        // Transform Applicants
        $transformApplicants = $applications->map(function ($applicant) {
            return $this->transform($applicant);
        });

        // Return the applications along with the associated student data
        return response()->json($transformApplicants, 200);
    }


    // Create a function that get the applcant's application by id
    public function getApplicantApplicationById(String $application_id)
    {
        // Get the authenticated user's company
        $auth = Auth::user()->company;

        // Ensure the authenticated user belongs to a company
        if (!$auth) {
            return response()->json(['message' => 'Company not found.'], 404);
        }
       
        // Check if the application belongs to the company
        $application = Application::with(['documentSubmissions.documentType', 'student.user', 'workPost.office', 'student.status', 'student.program.college', 'documentSubmissions.status', 'workPost.office.company.user'])
            ->where('id', $application_id)
            ->whereHas('workPost.office', function ($query) use ($auth) {
                $query->where('company_id', $auth->id);
            })
            ->first();

        // If the application is not found or does not belong to the company
        if (!$application) {
            return response()->json(['message' => 'Application not found or does not belong to your company.'], 404);
        }

        // Transform the application data
        $transformedApplication = $this->transform($application, 2);

        // Get Statuses
        $statuses = Status::whereIn('id', ['1', '2', '3', '5', '7'])->get();

        // Return the transformed application
        return response()->json([
            'application' => $transformedApplication,
            'statuses' => $statuses,
        ], 200);
    }
}
