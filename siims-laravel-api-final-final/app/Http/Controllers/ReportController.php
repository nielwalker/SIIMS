<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerformanceEvaluationRequest;
use App\Http\Resources\ReportResource;
use App\Models\Application;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Application Controller
     */
    private $applicationController;
    /**
     * CloudController Controller.
     */
    private $cloudController;
    /**
     * DocumentSubmission Controller
     */
    private $documentSubmissionController;
    public function __construct(ApplicationController $applicationController, CloudController $cloudController, DocumentSubmissionController $documentSubmissionController)
    {
        $this->applicationController = $applicationController;
        $this->cloudController = $cloudController;
        $this->documentSubmissionController = $documentSubmissionController;
    }
    
    /**
     * Summary of queryStudents: A private function that queries students.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function queryStudents(String $requestedBy) {
        // Get authenticated user
        $authUser = Auth::user();

        // Initialize variable 'query'
        $query = null;


        // ! FOR SUPERVISOR
        if($authUser->hasRole('supervisor') && $requestedBy === 'supervisor') {
             /**
              * - Fetch all application with application_status_id (10)
              * - That belongs to an office with supervisor
              */
            $query = Application::with(['student.user', 'workPost', 'applicationStatus'])->whereHas('workPost.office', function ($query) use ($authUser) {
                $query->where('supervisor_id', $authUser->id);
            });

        }

        // ! FOR COMPANY
        else if($authUser->hasRole('company') && $requestedBy === 'company') {
            
            $query = Application::with(['student.user', 'workPost', 'applicationStatus', 'workPost.office.supervisor'])->whereHas('workPost.office.company', function ($query) use ($authUser) {
                $query->where('id', $authUser->id);
            });
        }
        
        // ! FOR COORDINATOR
        else if($authUser->hasRole('coordinator') && $requestedBy === 'coordinator') {

            $query = Application::with(['student', 'workPost', 'applicationStatus', 'workPost.office.supervisor', 'workPost.office.company'])->whereHas('student', function ($query) use ($authUser) {
                $query->where('coordinator_id', $authUser->id);
            });
        }

        else {
            abort(404, 'No reports found');
        }

        // Return query
        return $query;
    }

       /**
     * Summary of findApplication: A public function that finds the application by ID.
     * @param string $id
     * @return TModel|null
     */
    private function findApplication(String $id) {
        $application = Application::find($id);

        return $application;
    }

    /**
     * Summary of getAllActiveStudents: A public function that gets all active students
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllActiveStudents(Request $request) {
        
        
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch Query where application_status is 10
        $query = $this->queryStudents($requestedBy)->where('application_status_id', 10); // Status 10

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('student.user', function ($q) use ($searchTerm) {
            $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate and transform the results
        $applications = $query->paginate($perPage);
        $applicationsResources = ReportResource::collection($applications);

        // Return resources
        return $applicationsResources;
    }

    /**
     * Summary of getAllCompletedStudents: A public function that gets all completed students.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllCompletedStudents(Request $request) {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch Query where application_status is 6
        $query = $this->queryStudents($requestedBy)->where('application_status_id', 6); // Status 6

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('student.user', function ($q) use ($searchTerm) {
            $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate and transform the results
        $applications = $query->paginate($perPage);
        $applicationsResources = ReportResource::collection($applications);

        // Return resources
        return $applicationsResources;
    }

    /**
     * Summary of submitAndCreateReport: A private function that submits and create a new report.
     * @param \Illuminate\Http\Request $request
     * @param string $applicationID
     * @param string $fileName
     * @param int $reportTypeID
     * @return TModel|\Illuminate\Http\JsonResponse
     */
    private function submitAndCreateReport(Request $request, String $applicationID, String $fileName, int $reportTypeID) {
        // Store the performance evaluation to the cloud
        $fileUrl = $this->cloudController->storeFileToCloud($request, "public/uploads/applications/{$applicationID}", $fileName);

        // Check if file url does not exist
        if(!$fileUrl) {
            return $this->jsonResponse(['message' => 'Something is wrong.'], 400);
        }

        // Create a new report
        $report = Report::create([
            "name" => 'Daily Time Record',
            "file_path" => $fileUrl,
            "application_id" => $applicationID,
            "report_type_id" => $reportTypeID, // Daily Time Record
        ]);

        // Return report
        return $report;
    }

    /**
     * Summary of uploadInsight: A public function that uploads the personal insights.
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadInsight(Request $request, String $application_id) {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'insight_report' => 'required|mimes:pdf|max:50240', // max size 10MB (adjust as needed)
        ]);

        // Find application
        $application = $this->findApplication($application_id);

        // Store the performance evaluation to the cloud
        $report = $this->submitAndCreateReport(request: $request, applicationID: $application->id, fileName: "insight_report", reportTypeID: 4);

         // Return response with status and message
         return $this->jsonResponse([
            'message' => "You submitted your insight report",
            'data' => $report,
        ], 201);
    }

    /**
     * Summary of uploadWeeklyReport: A public function that uploads the weekly report
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadWeeklyReport(Request $request, String $application_id) {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'weekly_report' => 'required|mimes:pdf|max:10240', // max size 10MB (adjust as needed)
        ]);

        // Find application
        $application = $this->findApplication($application_id);

        // Store the performance evaluation to the cloud
        $report = $this->submitAndCreateReport(request: $request, applicationID: $application->id, fileName: "weekly_report", reportTypeID: 2);

         // Return response with status and message
         return $this->jsonResponse([
            'message' => "You submitted your weekly report",
            'data' => $report,
        ], 201);
    }

    /**
     * Summary of uploadTimeRecord: A public function that uploads a time record.
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadTimeRecord(Request $request, String $application_id) {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'daily_time_record' => 'required|mimes:pdf|max:10240', // max size 10MB (adjust as needed)
        ]);

        // Find application
        $application = $this->findApplication($application_id);

        // Store the performance evaluation to the cloud
        $report = $this->submitAndCreateReport(request: $request, applicationID: $application->id, fileName: "daily_time_record", reportTypeID: 1);

         // Return response with status and message
         return $this->jsonResponse([
            'message' => "You submitted your daily time record",
            'data' => $report,
        ], 201);
    }

    /**
     * Summary of uploadPerformanceEvaluation: A public function that uploads a performance evaluation
     * @param \App\Http\Requests\PerformanceEvaluationRequest $request
     * @param string $application_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPerformanceEvaluation(PerformanceEvaluationRequest $request, String $application_id) {

        // Validated 
        $request->validated();

        // Find application
        $application = $this->findApplication($application_id);
        // $applicationID = $this->applicationController->findApplicationIdById($application_id);

        // Store the performance evaluation to the cloud
        $report = $this->submitAndCreateReport(request: $request, applicationID: $application->id, fileName: "performance_report", reportTypeID: 3);

        // Update Student Status and Application Status
        $application->application_status_id = 6;
        $application->save();
        $application->student->student_status_id = 6;
        $application->student->save();

        // Return response with status and message
        return $this->jsonResponse([
            'message' => "A student's performance is evaluated",
            'data' => $report,
        ], 201);

    }
}
