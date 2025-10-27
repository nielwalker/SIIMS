<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentTrackingResource;
use App\Http\Resources\ReportTrackingResource;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentTrackingController extends Controller
{
    
    /**
     * Summary of getAllTrackingDocuments: A public function that gets all document submissions in the latest applications
     * - Includes getting the total applications of the specific student.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTrackingDocuments(Request $request) {

        // Get authenticated user
        $authUser = Auth::user()->student;

        // Get latest application
        $latestApplication = $authUser->latestApplication;
        
        // Find Document Submission by application ID
        $documentSubmissions = DocumentSubmission::with(['documentStatus', 'documentType'])->where('application_id', $latestApplication->id)->get();


        // Find Reports belongs to application
        $reports = Report::with('reportType')->where('application_id', $latestApplication->id)->get();

        // Get Total Applications
        // $totalApplications = Application::where('student_id', $authUser->id)->count();

        // Transform collections
        $documentSubmissionsCollections = DocumentTrackingResource::collection($documentSubmissions);

        // Transform collections
        $reportsCollection = ReportTrackingResource::collection($reports);

        // Return
        return $this->jsonResponse([
            // 'total_applications' => $totalApplications,
            'documents' => $documentSubmissionsCollections,
            'reports' => $reportsCollection,
        ], 200);

    }

}
