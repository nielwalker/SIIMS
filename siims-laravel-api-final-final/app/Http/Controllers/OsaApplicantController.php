<?php

namespace App\Http\Controllers;

use App\Http\Requests\OsaApplicantDocumentRequest;
use App\Http\Requests\OsaApplicantDocumentStatusRequest;
use App\Models\Application;
use App\Models\DocumentSubmission;
use Illuminate\Http\Request;

class OsaApplicantController extends Controller
{



    public function updateDocumentStatus(OsaApplicantDocumentStatusRequest $request, String $applicant_id, String $application_id, String $document_id) {
        // Get validated status
        $validated = $request->validated();
    
        // Find document submission
        $document = DocumentSubmission::find($document_id);
    
        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }
    
        // Update document status
        $document->doc_status_id = $validated['status_id'];
        $document->save();
    
        /**
         * Check if all documents are status_id = 2 (Approved)
         */
        // Get all document submissions by application_id
        $documentSubmissions = DocumentSubmission::where('application_id', $application_id)->get();
    
        // Check if all doc_status_id are 2
        $allApproved = $documentSubmissions->every(function ( $submission) {
            return $submission->doc_status_id == 2; // Approve
        });
    
        if ($allApproved) {
            // Update the student status to 11
            $application = Application::with(['student'])->find($application_id);
    
            if ($application && $application->student) {
                $student = $application->student;
                $student->status_id = 11; // Status 11 - Ready for Deployment
                $student->save();

                $application->status_type_id = 11;
                $application->save();
            } else {
                return response()->json(['message' => 'Application or Student not found.'], 404);
            }
        }
    
        // Return response
        return response()->json(['message' => 'Document is updated.'], 201);
    }
    
    public function updateDocumentById(OsaApplicantDocumentRequest $request, String $applicant_id, String $application_id, String $document_id) {

        // Get validated $request
        $validated = $request->validated();

        // Get document submission
        $documentSubmission = DocumentSubmission::find($document_id);

        // Get all document submissions
        $documentSubmissions = DocumentSubmission::where('application_id', $application_id)->get();

        // Check if doc_type_id is exist then don't update
        foreach($documentSubmissions as $document) {
            if($document->doc_type_id === $validated['doc_type_id']) {
                return response()->json(['message' => 'Document Type already exist.'], 400);
            }
        }

        // Update document
        $documentSubmission->name = $validated['name'];
        $documentSubmission->doc_type_id = $validated['doc_type_id'];
        $documentSubmission->remarks = $validated['remarks'];
        $documentSubmission->save();

        // Return response 
        return response()->json(['message' => "Document is updated."], 201);

    }

    public function addNewDocument(OsaApplicantDocumentRequest $request,String $applicant_id, String $application_id) {

        // Get validated data
        $validated = $request->validated();

        // Find applicant's application
        $application = Application::where('student_id', $applicant_id)->find($application_id);

        // Check if application does exist
        if($application->isEmpty()) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        // Get document submissions from application_id
        $documentSubmissions = DocumentSubmission::where('application_id', $application->id)->get();

        // Loop and check if the doc_type_id is already exist
        foreach($documentSubmissions as $document) {

            if($document->doc_type_id === $validated['doc_type_id']) {
                return response()->json(['message' => 'Document Type already exist.'], 400);
            }

        }

        // Add new Document Submission
        DocumentSubmission::create([
            "application_id" => $application->id,
            "doc_type_id" => $validated['doc_type_id'],
            "name" => $validated['name'],
            "doc_status_id" => 7, // Incomplete
            "remarks" => $validated['remarks'],
        ]);

        return response()->json(['message' => 'New document is added.'], 201);

    }

    public function getApplicantApplications(String $applicant_id) {

        // Get all applicant's applications
        $applications = Application::where('student_id', $applicant_id)->orderBy('created_at', 'desc')->get();
        if(!$applications) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        return response()->json($applications, 200);

    }

    public function getApplicantApplicationById() {

    }

    // Transform applicants
    private function transform($applicant) {
        return [
            "id" => $applicant->id,
            "student_id" => $applicant->student_id,
            'job_title' => $applicant->workPost->title,
            "first_name" => $applicant->student->user->first_name,
            "middle_name" => $applicant->student->user->middle_name,
            "last_name" => $applicant->student->user->last_name,
            "email" => $applicant->student->user->email,
            "program" => $applicant->student->program->name,
            "phone_number" => $applicant->student->user->phone_number,
            "company_applied" => $applicant->workPost->office->company->name,
            "applied_date" => $this->formatDateOnlyDate($applicant->applied_date),
            "documents" => $applicant->documentSubmissions ? $applicant->documentSubmissions->map(function ($document) {
                return [
                    "file_path" => $document->file_path,
                    "file_type" => $document->documentType->name,
                ];
            }) : "No Document",
            "status" => $applicant->student->status->name,
        ];
    }

    //
    public function getAllApplicants() {

        // Get all applications and the owner (student)
        $applicants = Application::with(['student.user', 'student.status', 'workPost.office.company', 'student.program', 'documentSubmissions.documentType'])->orderBy('created_at', 'desc')->get();
        
        // Check if applicants does exist
        if(!$applicants) {
            return response()->json(['message' => 'Applicants does not exist.'], 404);
        }

        // return $applicants;
        
        // Transform applicants
        $transformApplicants = $applicants->map(function($applicant) {
            return $this->transform($applicant);
        });

        return response()->json($transformApplicants, 200);

    }
}
