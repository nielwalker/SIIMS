<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChairpersonEndorsementLetterRequest;
use App\Models\EndorsementLetterRequest;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChairpersonEndorsementLetterRequestController extends Controller
{


    public function testing(Request $request)
    {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'file' => 'required|mimes:pdf|max:10240', // max size 10MB (adjust as needed)
        ]);

        $file = $request->file('file');
        // $filePath = $file->store('endorsements', 'public'); // Store the file
        // Store the file in the specified directory
        $filePath = $file->store('public/uploads/endorsement_letters');

        // Replace 'public/' with 'storage/' to create the correct accessible URL
        $filePath = str_replace('public/', 'storage/', $filePath);


        // Check if a PDF file has been uploaded
        /*  if ($request->hasFile('endorsement_letter')) {
            // Store the uploaded PDF file in the designated folder
            $pdf_path = $request->file('endorsement_letter')->store('public/uploads/endorsement_letters');

        } else {
            return response()->json(['message' => 'No PDF file uploaded'], 400);
        } */

        // Return success response with the file path or any other relevant info
        return response()->json([

            'file_path' => $filePath
        ]);
    }

    /**
     * A function that updates the endorsement letter request by ID by adding a PDF file of the endorsement letter.
     * This function allows the chairperson to upload the endorsement letter (PDF) for a specific request.
     */
    public function addEndorsementLetter(Request $request, String $endorsement_request_id)
    {
        // Validate that the request contains the 'pdf_file' and that it is a valid PDF file with max size 10MB
        $request->validate([
            'pdf_file' => 'required|mimes:pdf|max:10240', // max size 10MB (adjust as needed)
        ]);

        // Find the existing endorsement request by its ID
        $endorsementRequest = EndorsementLetterRequest::find($endorsement_request_id);
        

        // Check if the endorsement request exists
        if (!$endorsementRequest) {
            return response()->json(['message' => 'Endorsement request not found'], 404);
        }

        // Check if a PDF file has been uploaded
        if ($request->hasFile('pdf_file')) {
            // Store the uploaded PDF file in the designated folder
            $pdf_path = $request->file('pdf_file')->store('public/uploads/endorsement_letters');

            // Update the endorsement request with the PDF file path
            $endorsementRequest->pdf_path = $pdf_path;
            $endorsementRequest->save();
        } else {
            return response()->json(['message' => 'No PDF file uploaded'], 400);
        }

        // Return success response with the file path or any other relevant info
        return response()->json([
            'message' => 'Endorsement letter uploaded successfully',
            'file_path' => $pdf_path
        ]);
    }

    // Transforms the endorsemen letter requests
    private function transform($endorsement_letter_request, int $option = 1)
{
    if ($option === 1) {
        return [
            "id" => $endorsement_letter_request->id,
            "student_id" => $endorsement_letter_request->student->id,
            "first_name" => $endorsement_letter_request->student->user->first_name,
            "middle_name" => $endorsement_letter_request->student->user->middle_name,
            "last_name" => $endorsement_letter_request->student->user->last_name,
            "email" => $endorsement_letter_request->student->user->email,
            "phone_number" => $endorsement_letter_request->student->user->phone_number,
            "description" => $endorsement_letter_request->description,
            "endorsement_file" => $endorsement_letter_request->endorsement_file,
            "remarks" => $endorsement_letter_request->remarks,
            "created_at" => $this->formatDateOnlyDate($endorsement_letter_request->created_at),
            "updated_at" => $this->formatDateOnlyDate($endorsement_letter_request->updated_at),
        ];
    }

    return [
        "id" => $endorsement_letter_request->id,
        'job_title' => $endorsement_letter_request->application->workPost->title,
        'company' => $endorsement_letter_request->application->workPost->office->company->name,
        'office' => $endorsement_letter_request->application->workPost->office->name,
        "description" => $endorsement_letter_request->description,
        "endorsement_file" => $endorsement_letter_request->endorsement_file,
        "remarks" => $endorsement_letter_request->remarks,
        "created_at" => $endorsement_letter_request->created_at,
        "updated_at" => $this->formatDateOnlyDate($endorsement_letter_request->updated_at),
        "requested_by_full_name" => trim(
            $endorsement_letter_request->student->user->first_name . ' ' .
            ($endorsement_letter_request->student->user->middle_name ? $endorsement_letter_request->student->user->middle_name . ' ' : '') .
            $endorsement_letter_request->student->user->last_name,
        ),
        "student" => $endorsement_letter_request->student,
        "endorse_students" => $endorsement_letter_request->endorseStudents->map(function ($endorse_student) {
            $user = $endorse_student->student->user;
            return [
                "student_id" => $endorse_student->student->id,
                "full_name" => trim(
                    $user->first_name . ' ' .
                    ($user->middle_name ? $user->middle_name . ' ' : '') .
                    $user->last_name
                ),
                "email" => $user->email,
                "phone_number" => $user->phone_number,
            ];
        }),
    ];
}


    public function getEndorsementLetterRequest($endorsement_request_id)
    {
        // Get authenticated user
        $user = Auth::user();

        $endorsementLetterRequest = EndorsementLetterRequest::with(['application.workPost.office.company', 'student.user', 'endorseStudents.student.user'])->find($endorsement_request_id);

        // return $endorsementLetterRequest;

        // Find chairperson in program
        /* $program = Program::where('chairperson_id', $user->id)->first();
        if (!$program) {
            return response()->json(['message' => 'Program not found.'], 404);
        }

        // Get the student in the same program
        $student = Student::where('program_id', $program->id)->first();
        if (!$student) {
            return response()->json(['message' => 'Student not found in the program.'], 404);
        }

        // Get endorsement letter request based on the student and find the specific request ID
        $endorsementLetterRequest = EndorsementLetterRequest::with(['application.workPost.office.company', 'student.user', 'endorseStudents.student.user'])->where('requested_by_id', $student->user_id)
            ->find($endorsement_request_id);

        if (!$endorsementLetterRequest) {
            return response()->json(['message' => 'Endorsement letter request not found.'], 404);
        } */

        // Transform Student
        $transformedEndorsementLetterRequest = $this->transform($endorsementLetterRequest, 2);



        // Return the endorsement letter request
        return response()->json($transformedEndorsementLetterRequest, 200);
    }


    // A function that views the list of endorsement letter requests
    public function getAllEndorsementLetterRequests()

    {

        /// Get the chairperson's program_id (the authenticated user)
        $user = Auth::user(); // Get the authenticated user (chairperson)
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        
        $program = $user->program;
        if (!$program) {
            return response()->json(['message' => 'No program assigned to the authenticated user.'], 403);
        }
        
        $programId = $program->id; // Assuming user has a program (chairperson overseeing a program)

        // Get all students in the chairperson's program
        $studentsInProgram = Student::where('program_id', $programId)->get();

        if ($studentsInProgram->isEmpty()) {
            return response()->json(['message' => 'No students found for this program.'], 404);
        }
        // Get all endorsement letter requests for the students in the chairperson's program
        $endorsementRequests = EndorsementLetterRequest::whereIn('requested_by_id', $studentsInProgram->pluck('id'))
            ->with('student.user', 'endorseStudents.student', 'application') // Eager load the related students
            ->get();

        // If no endorsement requests are found, return a message
        if ($endorsementRequests->isEmpty()) {
            return response()->json(['message' => 'No endorsement letter requests found.'], 404);
        }

        // Transform Endorsement Letter Request
        $transformedEndorsementLetterRequest = $endorsementRequests->map(function ($endorsementRequest) {
            return $this->transform($endorsementRequest);
        });

        // Return the list of endorsement letter requests with students
        return response()->json($transformedEndorsementLetterRequest, 200);
    }
}
