<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminStudentRequest;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{

    // Create a Student
    public function store(AdminStudentRequest $request) {

        // Get validated Credentials
        $validatedCredentials = $request->validated();
        // Get Program
        $program = Program::find($validatedCredentials['program_id']);
        // Check if program exist
        if(!$program) {
            return response()->json(['message' => 'Cannot create student because the program is not found.'] , 404);
        }

        
        // Store the user info
        $user = User::create([
            'id' => $validatedCredentials['id'],
            'first_name' => $validatedCredentials['first_name'],
            'middle_name' => $validatedCredentials['middle_name'],
            'last_name' => $validatedCredentials['last_name'],
            'email' => $validatedCredentials['email'],
            'password' => $validatedCredentials['password'],
            'gender' => $validatedCredentials['gender'],
            'phone_number' => $validatedCredentials['phone_number'],
            'street' => $validatedCredentials['street'],
            'barangay' => $validatedCredentials['barangay'],
            'city_municipality' => $validatedCredentials['city_municipality'],
            'province' => $validatedCredentials['province'],
            'postal_code' => $validatedCredentials['postal_code'],
        ]);

        // Check if created
        if(!$user) {
            return response()->json(['message' => 'Cannot create new student.'] , 500);
        }

        // Store User Role
        // role number: 7
        UserRole::create([
            'user_id' => $user['id'],
            'role_id' => 7,
        ]);

        // Store new Student
        Student::create([
            "id" => $user['id'],
            "user_id" => $user['id'],
            "program_id" => $validatedCredentials['program_id'],
            "coordinator_id" => $validatedCredentials['coordinator_id'],
            "age" => $validatedCredentials['age'],
            "date_of_birth" => $validatedCredentials['date_of_birth'],
        ]);

        // Return
        return response()->json(['message' => 'Student created.'] , status: 201);
    }

    // Get a Student
    public function getStudent(String $student_id)
    {

        // Find Student
        $student = Student::with('user')->find($student_id);

        // Check if student exist
        if (!$student) {
            return response()->json([
                "error" => "Student not found,"
            ], 404);
        }

        // Transformed Student attributes merged with User attributes
        $transformedStudent = [
            'id' => $student->id,
            'first_name' => $student->user->first_name,
            'middle_name' => $student->user->middle_name,
            'last_name' => $student->user->last_name,
            'age' => $student->age,
            'gender' => $student->user->gender,
            'email' => $student->user->email,
            'email_verified_at' => $student->user->email_verified_at,
            'program_id' => $student->program_id,
            'coordinator_id' => $student->coordinator_id,
            'isApplied' => $student->isApplied,
            'isPending' => $student->isPending,
            'date_of_birth' => $student->date_of_birth,
            'phone_number' => $student->user->phone_number,
            'street' => $student->user->street,
            'barangay' => $student->user->barangay,
            'city_municipality' => $student->user->city_municipality,
            'province' => $student->user->province,
            'postal_code' => $student->user->postal_code,
            'deleted_at' => $student->deleted_at,
            'created_at' => $student->created_at,
            'updated_at' => $student->updated_at,
        ];

        // Return response
        return $transformedStudent;
    }

    // Get all Students
    public function index()
    {

        // Get all student
        $students = User::has('student')->with('student')->get();

        // Transformed Student
        $students = $students->map(function ($student) {

            return [
                "id" => $student['id'],
                "first_name" => $student['first_name'],
                "middle_name" => $student['middle_name'],
                "last_name" => $student['id'],
                "age" => $student["student"]["age"],
                "coordinator_id" => $student["student"]["coordinator_id"],
                "program_id" => $student["student"]["program_id"],
                "email" => $student['id'],
                "email_verified_at" => $student['email_verified_at'],
                "gender" => $student['gender'],
                "phone_number" => $student['phone_number'],
                "street" => $student['street'],
                "barangay" => $student['barangay'],
                "city_municipality" => $student['city_municipality'],
                "province" => $student['province'],
                "isApplied" => $student["student"]["isApplied"],
                "isPending" => $student["student"]["isPending"],
                "date_of_birth" => $student["student"]["date_of_birth"],
                "postal_code" => $student['postal_code'],
                "created_at" => $student['created_at'],
                "updated_at" => $student['updated_at'],
                "deleted_at" => $student['deleted_at'],
            ];
        });

        // Return students
        return response()->json($students, status: 200);
    }
}
