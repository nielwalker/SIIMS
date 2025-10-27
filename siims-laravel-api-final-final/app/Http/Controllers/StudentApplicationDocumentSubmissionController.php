<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\EndorsementLetterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentApplicationDocumentSubmissionController extends Controller
{

    private function transformStepOneDocuments($document)
    {

        return [
            "id" => $document->id,
            "doc_type_id" => $document->doc_type_id,
            "name" => $document->documentType ? $document->documentType->name : "Invalid Doc Type Name",
            "file_path" => $document->file_path,
            "status" => $document->status ? $document->status->name : "Invalid Status Name",
            "can_change" => $document->status->id !== 2, // Approve
        ];
    }

    public function getStepTwoAllDocuments(String $application_id)
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

        $document_submissions = DocumentSubmission::with(['documentType', 'status'])->where('application_id', $application_id)
                ->whereIn('doc_type_id', [1, 2, 3, 5])
                ->get();

       

        // Transform document_submissions
        $transformedDocumentSubmissions = $document_submissions->map(function ($document) {
            return $this->transformStepOneDocuments($document);
        });

        return response()->json($transformedDocumentSubmissions, 200);
    }
}
