<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;

class OsaApplicationController extends Controller
{
    
    private function transform($application) {
      
        return [
            "id" => $application->id,
            "student_id" => $application->student_id,
            "first_name" => $application->student->user->first_name,
            "middle_name" => $application->student->user->middle_name,
            "last_name" => $application->student->user->last_name,
            "email" => $application->student->user->email,
            "phone_number" => $application->student->user->phone_number,
            "applied_date" => $this->formatDateOnlyDate($application->applied_date),
            "title" => $application->workPost->title, 
            "company" => $application->workPost->office->company->name,
            "office" => $application->workPost->office->name,
            "status_id" => $application->status_id,
            "status" => $application->student->status->name,
            "program" => $application->student->program->name,
            "date_of_birth" => $application->student->date_of_birth,
            "college" => $application->student->program->college->name,
            "office_phone_number" => $application->workPost->office->phone_number,
            "documents" => $application->documentSubmissions ? $application->documentSubmissions->map(function ($document) {
                return [
                    "id" => $document->id,
                    "document_type" => $document->documentType->name,
                    "doc_status_id" => $document->doc_status_id,
                    "name" => $document->name,
                    "file_path" => $document->file_path,
                    "remarks" => $document->remarks,
                    "created_at" => $document->created_at,
                    "updated_at" => $document->updated_at,
                    "status" => $document->status->name,
                    "can_modify" => $document->doc_status_id !== 2, // Approve
                ];
            }) : "No document not found",
           
        ];

    }

    public function getApplicationById(String $application_id) {

        // Find application
        $application = Application::with(['workPost.office.company', 'student.user', 'student.status', 'documentSubmissions.status', 'student.program.college', 'documentSubmissions.documentType'])->find($application_id);
        // return $application;
        // Transform Application
        $transformedApplication = $this->transform($application);

        // Return response
        return response()->json($transformedApplication, 200);
    }
}
