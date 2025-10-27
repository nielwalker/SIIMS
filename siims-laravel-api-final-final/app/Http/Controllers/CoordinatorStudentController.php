<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoordinatorStudentStatusRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoordinatorStudentController extends Controller
{

    public function updateStudentStatus(CoordinatorStudentStatusRequest $request)
    {

        // Get validated data
        $validated = $request->validated();
        
        // Retrieve the list of students based on provided IDs
        $students = Student::whereIn('id', $validated['ids'])->get();

        // Arrays to keep track of the results
        $studentsNotReady = [];
        $studentsUpdated = [];

        foreach ($students as $student) {
            if ($student->status_id === 11) {
                // Update the status to "Deployed" (status_id = 12)
                $student->status_id = 12;
                $student->student_status_id = 5;
                $student->save();
                
                $studentsUpdated[] = $student->id;
            } else {
                // Collect IDs of students not in "Ready for deployment" status
                $studentsNotReady[] = $student->id;
            }
        }

        // Build the response
        $response = [
            'message' => 'Student status update completed.',
            'updated_students' => $studentsUpdated,
            'not_ready_students' => $studentsNotReady,
        ];

        return response()->json($response, 201);
    }
    private function transform($student)
    {
        // Resolve company name with priority: direct assignment > latest application > work experiences
        $companyName = "—";
        try {
            if ($student->company) {
                $companyName = $student->company->name;
            } else if (
                $student->latestApplication &&
                $student->latestApplication->workPost &&
                $student->latestApplication->workPost->office &&
                $student->latestApplication->workPost->office->company
            ) {
                $companyName = $student->latestApplication->workPost->office->company->name;
            } else if ($student->workExperiences && $student->workExperiences->count() > 0) {
                $latestWE = $student->workExperiences->sortByDesc('created_at')->first();
                if ($latestWE && $latestWE->company_name) {
                    $companyName = $latestWE->company_name;
                }
            }
        } catch (\Throwable $e) {
            // keep default "—" if anything fails
        }

        return [
            "id" => $student->id,
            "first_name" => $student->user->first_name,
            "middle_name" => $student->user->middle_name,
            "last_name" => $student->user->last_name,
            "email" => $student->user->email,
            "gender" => $student->user->gender,
            "phone_number" => $student->user->phone_number,
            "street" => $student->user->street,
            "barangay" => $student->user->barangay,
            "city_municipality" => $student->user->city_municipality,
            "province" => $student->user->province,
            "postal_code" => $student->user->postal_code,
            'status' => $student->status ? $student->status->name : '',
            // expose company for frontend convenience
            'company' => $companyName,
            'company_name' => $companyName,
        ];
    }

    public function getStudentById(String $student_id)
    {
        // Get auth coordinator
        $user = Auth::user();
        if (!$user || !$user->coordinator) {
            return response()->json(['message' => 'User not authenticated as coordinator.'], 401);
        }
        $auth = $user->coordinator;

        // Get program
        $program = $auth->program;
        if (!$program) {
            return response()->json(['message' => 'Coordinator program not found.'], 404);
        }

        // Get all student where coordinator_id
        $student = Student::with([
                'user',
                'status',
                'company',
                'latestApplication.workPost.office.company',
                'workExperiences'
            ])
            ->where('program_id', $program->id)
            ->where('coordinator_id', $auth->id)
            ->find($student_id);
        // Check if student exist
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        // Transform Student
        $transformStudent = $this->transform($student);

        return response()->json($transformStudent);
    }

    /**
     * Summary of getAllStudents: A public function that gets all students
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllStudents()
    {

        // Get auth coordinator
        $user = Auth::user();
        if (!$user || !$user->coordinator) {
            return response()->json(['message' => 'User not authenticated as coordinator.'], 401);
        }
        $auth = $user->coordinator;

        // Get program
        $program = $auth->program;
        if (!$program) {
            return response()->json(['message' => 'Coordinator program not found.'], 404);
        }


        // Get all students where coordinator_id
        $students = Student::with([
                'user',
                'status',
                'company',
                'latestApplication.workPost.office.company',
                'workExperiences'
            ])
            ->where('program_id', $program->id)
            ->where('coordinator_id', $auth->id)
            ->get();

        // Transform student
        $transformedStudent = $students->map(function ($student) {
            return $this->transform($student);
        });

        // Return student
        return response()->json($transformedStudent, 200);
    }
}
