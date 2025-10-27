<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentEndorsementLetterRequest;
use App\Models\Application;
use App\Models\Company;
use App\Models\EndorsementLetterRequest;
use App\Models\EndorseStudent;
use App\Models\Program;
use App\Models\Student;
use App\Models\WorkPost;
use App\Services\ActionLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Endorsement Letter
class StudentEndorsementLetterController extends Controller
{

    public function requestEndorsementLetterByApplicationId(StudentEndorsementLetterRequest $request, String $application_id) {

        // Get validated Credentials
        $validated = $request->validated();
        // Get auth user
        $auth = Auth::user()->student;

        // Find application by student_id
        $application = Application::where('student_id', $auth->id)->find($application_id);
     
        // Find endorsement request by application_id
        $existingEndorsementRequest = EndorsementLetterRequest::where('requested_by_id')->find($application_id);
        if($existingEndorsementRequest) {
            return response()->json(['message' => 'You already have requested an endorsement letter for this application.'], 400);
        }

        // Create an endorsement letter request
        $endorsement_letter_request = EndorsementLetterRequest::create([
            "requested_by_id" => $auth->id,
            "application_id" => $application_id,
            "description" => $validated['description'],
            "status_id" => 1,
        ]);

         // Create endorse student records for each student ID in the request
         foreach ($validated['student_ids'] as $student_id) {
            EndorseStudent::create([
                "endorse_req_id" => $endorsement_letter_request->id,
                "student_id" => $student_id['student_id'], // Add each student ID
            ]);
        }

        // Return Response
        return response()->json(['message' => 'Request sent.'], 201);

    }

    /* public function requestEndorsementLetterByJobId(StudentEndorsementLetterRequest $request, String $job_id)
    {

        // Get validated data
        $validated = $request->validated();

        // Get auth student
        $student = Auth::user()->student;

        // Create endorsement letter request record
        $endorsement_letter_request = EndorsementLetterRequest::create([
            "requested_by_id" => $student->id,
            "work_post_id" => $job_id,
        ]);

        // Create endorse student records for each student ID in the request
        foreach ($validated['student_ids'] as $student_id) {
            EndorseStudent::create([
                "endorse_req_id" => $endorsement_letter_request->id,
                "student_id" => $student_id['student_id'], // Add each student ID
            ]);
        }

        // Return Response
        return response()->json(['message' => 'Request sent.'], 201);
    } */

    // A function that creates a record of endorsement letter request by job_id
    public function requestEndorsement(Request $request, String $job_id, ActionLogger $actionLogger)
    {
        // Get the authenticated user (student)
        $user = Auth::user()->student;

        // Get the job by ID
        $job = WorkPost::find($job_id);

        // Check if the job exists
        if (!$job) {
            $actionLogger->logAction(
                actionType: 'Request',
                entity: 'Job',
                entityId: $job_id,
                description: "Endorsement letter request failed. Job not found.",
                status: 'Failed',
                httpCode: "404",
            );

            return response()->json(["message" => "Job not found."], 404);
        }

        // Validate that 'students' is an array and contains at least two student IDs
        $request->validate([
            'students' => 'required|array|min:2',
            'students.*' => 'exists:students,id', // Ensure each student ID exists in the database
        ]);

        // Create the EndorsementLetterRequest record
        $endorsementLetterRequest = EndorsementLetterRequest::create([
            'requested_by_id' => $user->id,
            'work_post_id' => $job->id
        ]);

        // Loop through the provided students to create EndorseStudent records
        foreach ($request->students as $studentId) {
            // Ensure that the student exists and isn't the requesting student
            $student = Student::find($studentId);
            if ($student && $student->id !== $user->id) {
                // Create an EndorseStudent record for each student
                EndorseStudent::create([
                    'endorse_req_id' => $endorsementLetterRequest->id,
                    'student_id' => $student->id,
                ]);
            }
        }

        // Log the endorsement letter request
        $actionLogger->logAction(
            actionType: 'Create',
            entity: 'EndorsementLetterRequest',
            entityId: $endorsementLetterRequest->id,
            description: 'Student requested an endorsement letter for a job.',
            status: 'Success',
            httpCode: '201',
        );

        // Return success response
        return response()->json([
            'message' => 'Endorsement letter request submitted successfully.',
            'endorsement_letter_request' => $endorsementLetterRequest
        ], 201);
    }

    // A function that creates a request endorsement letter
    public function createRequestEndorsementLetter(StudentEndorsementLetterRequest $request)
    {

        // Get user
        $user = Auth::user()->student;

        // Check if user exist
        if (!$user) {
            return response()->json(['message' => 'Student not found.']);
        }

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Check if work post does exist
        $workPost = WorkPost::has('office')->with(['office.company'])->find($validatedCredentials['work_post_id']);
        if (!$workPost) {
            return response()->json(["message" => "Job not found..."], 404);
        }

        // Check if each of student id does exist
        $studentIds = $validatedCredentials['student_ids'];

        // Get all matching student IDs from the students table
        $existingStudentIds = Student::whereIn('id', $studentIds)->pluck('id')->toArray();

        // Step 3: Find any missing student IDs
        $missingStudentIds = array_diff($studentIds, $existingStudentIds);

        if (!empty($missingStudentIds)) {
            return response()->json([
                'message' => 'Some student do not exist.',
                'missing_student_ids' => $missingStudentIds,
            ], 404);
        }

        // Store new endorsement letter request (Database Table: tbl_endorsement_req)
        $endorsemenLetterRequest = EndorsementLetterRequest::create([
            "work_post_id" => $validatedCredentials['work_post_id'],
            "requested_by_id" => $user['id'],
        ]);

        // Check if the endorsement letter is created
        if (!$endorsemenLetterRequest) {
            return response()->json([
                "message" => 'Endorsement Request cannot be created.'
            ], 400);
        }

        // Store also the included in the endorsement letter (Database Table: tbl_endorse_student)
        foreach ($validatedCredentials['student_ids'] as $student_id) {
            EndorseStudent::create([
                "endorse_req_id" => $endorsemenLetterRequest['id'],
                "student_id" => $student_id,
            ]);

            // Updates each of the student's isPending and last_applied_at
            $student = Student::find($student_id);
            $student->isPending = true;
            $student->last_applied_at = Carbon::now();
            $student->save();
        }

        // Return status 201 indicated successful creation
        return response()->json(['message' => 'An endorsement letter has been requested.'], 201);
    }

    // Create an endorsement letter request
    public function store(StudentEndorsementLetterRequest $request)
    {

        // Get user
        $user = Auth::user()->student;

        // Check if user exist
        if (!$user) {
            return response()->json(['message' => 'Student not found.']);
        }

        // Get validated Credentials
        $validatedCredentials = $request->validated();

        // Check if work post does exist
        $workPost = WorkPost::has('office')->with(['office.company'])->find($validatedCredentials['work_post_id']);
        if (!$workPost) {
            return response()->json(["message" => "Job not found..."], 404);
        }

        // Store request and check
        $endorsemenLetterRequest = EndorsementLetterRequest::create([
            "program_id" => $user['program_id'],
            "work_post_id" => $validatedCredentials['work_post_id'],
            "requested_by_id" => $user['id'],
        ]);

        // Check endorsement creation
        if (!$endorsemenLetterRequest) {
            return response()->json(["message" => "Request not created"], 404);
        }

        // Return
        return response()->json(["message" => "Endorsement letter requested."], 200);
    }

    // Get all endorsement letter request
    public function index()
    {

        // Get User
        $user = Auth::user();

        // Check if user is a student
        if (!$user->student) {
            return response()->json(['message' => 'User is not a student.'], 403);
        }

        // Get endorsement letter
        $endorsementLetterRequests = EndorsementLetterRequest::where('requested_by_id', $user->student->id)->get();

        return $endorsementLetterRequests;
    }

    // Create an endorsement letter request based on the selected company
    public function storeEndorsementLetterRequestbyCompanyId(StudentEndorsementLetterRequest $request, String $company_id)
    {

        // Get user
        $user = Auth::user()->student;


        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find company
        $company = Company::find($company_id);

        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.']);
        }

        // Create endorsement letter

        return $user;
    }
}
