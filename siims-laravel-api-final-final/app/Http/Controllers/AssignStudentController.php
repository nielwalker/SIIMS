<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignStudentRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignStudentController extends Controller
{

    /**
     * The coordinator controller
     */
    private $coordinatorController;
   
    public function __construct(CoordinatorController $coordinatorController) {
        $this->coordinatorController = $coordinatorController;
     
    }

    /**
     * Summary of assignStudents: A public function that assigns the students to a coordinator
     * @param \App\Http\Requests\AssignStudentRequest $assignStudentRequest
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function assignStudents(AssignStudentRequest $assignStudentRequest)
{

    // Get authenticated user
    $authUser = Auth::user();

    // Validate the request
    $validatedCredentials = $assignStudentRequest->validated();

    // Find Coordinator's ID
    $coordinatorID = $this->coordinatorController->findCoordinatorIdById($validatedCredentials['coordinator_id']);

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
        $student->update(['coordinator_id' => $coordinatorID]);
    }

    // Return success response
    return $this->jsonResponse(['message' => 'Students successfully assigned to the coordinator.'], 201);
}

}
