<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentMarkStatusRequest;
use App\Http\Resources\DocumentSubmissionResource;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\EndorsementLetterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentSubmissionController extends Controller
{

    /**
     * Summary of queryDocumentSubmission: A public function that gets the query of document submission
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function queryDocumentSubmission() {

        return DocumentSubmission::with(['documentStatus', 'documentType.documentStep']);

    }

    /**
     * Summary of markDocumentStatus: A public function that updates the document status.
     * @param string $document_id
     * @param \App\Http\Requests\DocumentMarkStatusRequest $documentMarkStatusRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function markDocumentStatus(String $document_id, DocumentMarkStatusRequest $documentMarkStatusRequest) {
        // Get authenticated user 
        $authUser = Auth::user();

        // Get validated
        $validated = $documentMarkStatusRequest->validated();

        // Find document submission by ID
        $documentSubmission = DocumentSubmission::find($document_id);

        // Update and save status
        $documentSubmission->document_status_id = $validated['document_status_id'];
        $documentSubmission->save();


        // ! FOR OSA PART FUNCTION
        if($authUser->hasRole('osa')) {
            // Get the parent of document submission
            $application = $documentSubmission->application;

            // * FOR OSA PROCESSING
            // Get all document submissions related to this application
            $documentSubmissions = $application->documentSubmissions;

            // Check if all document submissions have a document_status_id of 4 (Approved)
            $allApproved = $documentSubmissions->every(function ($docSubmission) {
                return $docSubmission->document_status_id == 4; // 4 is Approved
            });

            // If all document submissions are approved
            if($allApproved) {
                // Set Application Status to 9 (Ready for Deployment)
                $application->application_status_id  = 9;
                $application->save();

                // Set Student Status to 4 (Ready for Deployment)
                $application->student->student_status_id = 4;
                $application->student->save();

                return $this->jsonResponse(
                    ['message' => 'All documents approved. Student is waiting for deployment.']
                , 201);
            }

            else {
                 // Find and return documentSubmission
                return $this->jsonResponse([
                    'message' => 'A document is updated.',
                    'data' => $this->queryDocumentSubmission()->find($documentSubmission->id)
                ], 201);
            }
          
        }

        // ! FOR COMPANY
        else if($authUser->hasRole('company')) {
            
            // Find and return documentSubmission
            return $this->jsonResponse([
                'message' => 'A document is updated.',
                'data' => $this->queryDocumentSubmission()->find($documentSubmission->id)
            ], 201);

        }

       
    }

    /**
     * Summary of getAllDocumentsByApplicationID: A public function that gets all documents by application ID
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllDocumentsByApplicationID(Request $request, String $application_id) {
       
        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get authenticated user
        $authUser = Auth::user();

        // ! FOR COMPANY
        if($authUser->hasRole('company') && $requestedBy === 'company') {

            // Find application by ID
            $application = Application::whereHas('workPost.office.company', function ($query) use ($authUser) {
                $query->where('id', $authUser->id);
            })->find($application_id);
            
        }

        // ! FOR COORDINATOR
        else if($authUser->hasRole('coordinator') && $requestedBy === 'coordinator') {
            // Get coordinator
            $coordinatorID = $authUser->coordinator->id;

            // Find the application that belongs to student and that student belongs to a coordinator.
            $application = Application::whereHas('student', function ($query) use ($coordinatorID) {
                $query->where('coordinator_id', $coordinatorID);
            })->find($application_id);
        }

        // ! FOR OSA
        else if($authUser->hasRole('osa') && $requestedBy === 'osa') {
                // Find the application that belongs to student and that student belongs to a coordinator.
            $application = Application::find($application_id);
        }

        else {
            abort(400, 'Error');
        }
        // Check if application does exist
        if(!$application) {
            return $this->jsonResponse(['message' => 'Application not found.'], 404);
        }

        // Find Document Submission by application id
        $documents = $this->queryDocumentSubmission()->where('application_id', $application->id)->get();

        // Return
        return $this->jsonResponse(DocumentSubmissionResource::collection($documents), 200);
    }
    public function createDocumentSubmission(array $values) {

        // Mass Assign Document Submission
        $documentSubmission = DocumentSubmission::create($values);

        // Return the created document
        return $documentSubmission;

    }
    
    /**
     * Summary of transformStepOneDocuments: A public function that transformed the document submission.
     * @param mixed $document
     * @return array
     */
    public function transformStepOneDocuments($document)
    {

        return [
            "id" => $document->id,
            "doc_type_id" => $document->doc_type_id,
            "name" => $document->documentType ? $document->documentType->name : "Invalid Doc Type Name",
            "file_path" => $document->file_path,
            "status" => $document->documentStatus->name,
            // "status" => $document->status ? $document->status->name : "Invalid Status Name",
            "can_change" => $document->status->id !== 2, // Approve
        ];
    }

    /**
     * Summary of getStepOneAllDocuments: A public function that gets the Step One Document Submission.
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getStepOneAllDocuments(String $application_id)
    {
        // Get auth student
        $user = Auth::user()->student;

        // Find student application
        $application = Application::find($application_id);
        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        // Check if the application belongs to the authenticated student
        if ($application->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // Create Endorsement Letter Request Instance
        $document_submissions = new DocumentSubmission();

        // Check the latest status of endorsement letter status
        $latestEndorsementLetterRequestStatus = EndorsementLetterRequest::where('requested_by_id', $user->id)->latest()->first();

        if ($latestEndorsementLetterRequestStatus && ($latestEndorsementLetterRequestStatus->status_id === 2 || $latestEndorsementLetterRequestStatus->endorsement_letter_request_status_id && $latestEndorsementLetterRequestStatus->endorsement_letter_request_status_id === 3)) {

            // Get all document submissions where application_id matches and doc_type is 13, 9, 3, 2
            $document_submissions = DocumentSubmission::with(['documentType', 'status', 'documentStatus'])->where('application_id', $application_id)
                ->whereIn('doc_type_id', [13, 9, 3, 2])
                ->get();
        } else {
            // Get all document submissions where application_id matches and doc_type is 13, 9, 3, 2
            $document_submissions = DocumentSubmission::with(['documentType', 'status', 'documentStatus'])->where('application_id', $application_id)
                ->whereIn('doc_type_id', [13, 9, 3])
                ->get();
        }


        // Transform document_submissions
        $transformedDocumentSubmissions = $document_submissions->map(function ($document) {
            return $this->transformStepOneDocuments($document);
        });

        // Return transformed document submittions
        return response()->json($transformedDocumentSubmissions, 200);
    }

    /**
     * Summary of getStepTwoAllDocuments: A public function that gets the Step Two Document Submission.
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getStepTwoAllDocuments(String $application_id) {

        // Get auth student
        $user = Auth::user()->student;

        // Find student application
        $application = Application::find($application_id);
        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        // Check if the application belongs to the authenticated student
        if ($application->student_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // Get all document submissions where application_id matches and doc_type is 1, 2, or 3
        $document_submissions = DocumentSubmission::with(['documentType', 'status'])->where('application_id', $application_id)
            ->whereNotIn('doc_type_id', [1, 2, 3, 5])
            ->get();

        // Transform document_submissions
        $transformedDocumentSubmissions = $document_submissions->map(function ($document) {
            return $this->transformStepOneDocuments($document);
        });

        return response()->json($transformedDocumentSubmissions, 200);

    }

    /**
     * Summary of generateStepOneDocumentSubmissions: A public function that generates a Step One Document Submissions
     * @param String $application_id
     * @return void
     */
    public function generateStepOneDocumentSubmissions(String $application_id)
    {
        $documents = [
            [
                "application_id" => $application_id,
                "doc_type_id" => 13,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Certificate of Orientation",
            ],
            [
                "application_id" => $application_id,
                "doc_type_id" => 9,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Application Letter",
            ],
            [
                "application_id" => $application_id,
                "doc_type_id" => 3,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Resume",
            ],
            [
                "application_id" => $application_id,
                "doc_type_id" => 2,
                "doc_status_id" => 7,
                "remarks" => "",
                "name" => "Endorsement Letter",
            ],
        ];

        foreach ($documents as $document) {
            DocumentSubmission::create($document);
        }
    }


    /**
     * Summary of generateStepTwoDocumentSubmissions: A public function that generates Step Two Document Submissions
     * @param String $application_id
     * @return void
     */
    public function generateStepTwoDocumentSubmissions(String $application_id)
    {
        /**
         * - Standary Document Submissios for Step Two
         * - Medical Certificate            - 4
         * - Certificate of Registration    - 12
         * - Waiver                         - 14
         */
        DocumentSubmission::create([
            "application_id" => $application_id,
            "doc_type_id" => 4,
            "doc_status_id" => 7,
            "remarks" => "",
            "name" => "Medical Certificate",
        ]);

        DocumentSubmission::create([
            "application_id" => $application_id,
            "doc_type_id" => 14,
            "doc_status_id" => 7,
            "remarks" => "",
            "name" => "Waiver",
        ]);

        DocumentSubmission::create([
            "application_id" => $application_id,
            "doc_type_id" => 12,
            "doc_status_id" => 7,
            "remarks" => "",
            "name" => "Certificate of Registration",
        ]);
    }

    /**
     * Summary of updateDocumentSubmission: A public function where a document submission is updated by status.
     * @param \App\Models\DocumentSubmission $documentSubmission
     * @param int $status_id
     * @return void
     */
    public function updateDocumentSubmission(DocumentSubmission $documentSubmission, int $status_id)
    {
        // Update the doc_status_id for the given DocumentSubmission
        // $documentSubmission->doc_status_id = $status_id;
        $documentSubmission->document_status_id = $status_id;
        $documentSubmission->save();
    }
}
