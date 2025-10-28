<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChairpersonStudentRequest;
use App\Models\Coordinator;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChairpersonStudentController extends Controller
{

    public function importStudents(Request $request) {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048', // Ensure it's a CSV file
        ]);

        $file = $request->file('file');

        $data = [];

        // Open the file for reading
        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $header = null;

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $header = $row; // Use the first row as headers
                } else {
                    $data[] = array_combine($header, $row); // Combine headers with row values
                }
            }

            fclose($handle);
        }

        // Return the parsed data
        return response()->json([
            'message' => 'CSV imported successfully',
            'data' => $data,
        ]);
    }

    public function assignStudents(ChairpersonStudentRequest $request)
{

    // Validate the request
    $validatedCredentials = $request->validated();

    // Get auth user's program (chairperson)
    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'User not authenticated.'], 401);
    }
    
    $program = $user->program;
    if(!$program) {
        return response()->json(['message' => 'Chairperson program not found.'], 404);
    }

    // Find coordinator by coordinator_id in the program
    $coordinator = Coordinator::where('program_id', $program->id)->find($validatedCredentials['coordinator_id']);
    if(!$coordinator || !$coordinator->program_id) {
        return response()->json(['message' => 'Coordinator not found.'], 404);
    }

    // Assign each student in the student_ids array to the coordinator
    foreach ($validatedCredentials['student_ids'] as $studentData) {
        $studentId = $studentData['student_id'];

        // Find the student by ID
        $student = Student::find($studentId);

        // Check if the student exists
        if (!$student) {
            return response()->json([
                'message' => "Student with ID $studentId not found.",
            ], 404); // HTTP Not Found
        }

        // Assign the coordinator_id to the student
        $student->update(['coordinator_id' => $validatedCredentials['coordinator_id']]);
    }

    // Return success response
    return response()->json([
        'message' => 'Students successfully assigned to the coordinator.',
    ], 201); // HTTP OK
}

    /**
     * A public function that adds a new record of student
     */
    public function addNewStudent(ChairpersonStudentRequest $request)
    {

        // Get validated Credentials
        $validatedCredentials = $request->validated();

        // Get authenticated user
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Get the program assigned to the chairperson
        $program = $user->program; // Assuming there's a `program` relationship in the User model
        if (!$program) {
            return response()->json([
                'message' => 'You do not have a program assigned. Cannot add student.'
            ], 403); // HTTP Forbidden
        }

        // Check if the student's program matches the chairperson's program
        if ($program->id != $validatedCredentials['program_id']) {
            return response()->json([
                'message' => 'The program ID does not match your assigned program.'
            ], 403); // HTTP Forbidden
        }

        // Check if the program exists
        if (!Program::find($validatedCredentials['program_id'])) {
            return response()->json([
                'message' => 'Program not found.'
            ], 404); // HTTP Not Found
        }

        // Create the user record
        $user = User::create([
            'id' => $validatedCredentials['id'],
            'first_name' => $validatedCredentials['first_name'],
            'middle_name' => $validatedCredentials['middle_name'],
            'last_name' => $validatedCredentials['last_name'],
            'email' => $validatedCredentials['email'],
            'password' => bcrypt($validatedCredentials['password']),
            'gender' => $validatedCredentials['gender'],
            'phone_number' => $validatedCredentials['phone_number'],
            'street' => $validatedCredentials['street'],
            'barangay' => $validatedCredentials['barangay'],
            'city_municipality' => $validatedCredentials['city_municipality'],
            'province' => $validatedCredentials['province'],
            'postal_code' => $validatedCredentials['postal_code'],
        ]);

        // Check if the user was created successfully
        if (!$user) {
            return response()->json(['message' => 'Failed to create the user record.'], 500);
        }

        // Assign the student role to the user (role ID 7 for students)
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => 7,
        ]);

        // Create the student record
        $student = Student::create([
            'id' => $user->id,
            'user_id' => $user->id,
            'program_id' => $validatedCredentials['program_id'],
            'coordinator_id' => $validatedCredentials['coordinator_id'],
            // Allow optional direct company assignment on create
            'company_id' => $validatedCredentials['company_id'] ?? null,
            'age' => $validatedCredentials['age'],
            'date_of_birth' => $validatedCredentials['date_of_birth'],
        ]);

        // Check if the student record was created successfully
        if (!$student) {
            return response()->json(['message' => 'Failed to create the student record.'], 500);
        }

        // Return success response
        return response()->json([
            'message' => 'Student added successfully.',
            'student' => $student,
        ], 201); // HTTP Created

    }

    /**
     * A public function that gets all the list of students at the program only
     * Note: The chairperson must have a program assigned to.
     * 
     */
    public function getAllStudentsByProgram()
    {
        // Get auth user
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        // Ensure the user has a program assigned
        $program = $user->program; // Assuming there's a `program` relationship in the User model

        if (!$program) {
            return response()->json([
                'message' => 'No program assigned to the authenticated user.'
            ], 403); // HTTP Forbidden
        }
        // Get all students enrolled in the program
        // $students = $program->students; // Assuming there's a `students` relationship in the Program model

        // Get all students with their latest application and company info
        $students = Student::with(['user', 'coordinator.user', 'company', 'latestApplication.workPost.office.company', 'workExperiences'])->where('program_id', $program->id)->get();

        $transformedStudents = $students->map(function ($student) {
            return $this->transform($student);
        });

        // Return the list of students with a success response
        return response()->json($transformedStudents, 200); // HTTP OK
    }

    private function transform($student)
    {
        // Get company name with priority: direct assignment > application > work experience
        $companyName = "—";
        
        // First priority: Direct company assignment
        if ($student->company) {
            $companyName = $student->company->name;
        }
        // Second priority: Latest application
        else if ($student->latestApplication && 
            $student->latestApplication->workPost && 
            $student->latestApplication->workPost->office && 
            $student->latestApplication->workPost->office->company) {
            $companyName = $student->latestApplication->workPost->office->company->name;
        }
        // Third priority: Work experiences
        else if ($student->workExperiences && $student->workExperiences->count() > 0) {
            $latestWorkExperience = $student->workExperiences->sortByDesc('created_at')->first();
            if ($latestWorkExperience && $latestWorkExperience->company_name) {
                $companyName = $latestWorkExperience->company_name;
            }
        }

        return [
            "id" => $student->id,
            // expose coordinator_id so frontend can filter by selected coordinator
            "coordinator_id" => $student->coordinator_id,
            "first_name" => $student->user->first_name,
            "middle_name" => $student->user->middle_name,
            "last_name" => $student->user->last_name,
            "email" => $student->user->email,
            "coordinator" => $student->coordinator
                ? trim(
                    $student->coordinator->user->first_name . ' ' .
                        $student->coordinator->user->middle_name . ' ' .
                        $student->coordinator->user->last_name
                )
                : "No coordinator",
            "company" => $companyName,
            "company_name" => $companyName,
        ];
    }
}
