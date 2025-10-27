<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentReportController extends Controller
{
    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct()
    {
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    public function getStudentReports()
{
    // Get Coordinator
    $coordinator = Coordinator::find($this->user->id);

    if (!$coordinator) {
        return response()->json(['error' => 'Coordinator not found'], 404);
    }

    // Get Coordinator's Students where status is 4 or 12, including their latest application
    $students = Student::where('coordinator_id', $coordinator->id)
        ->where(function ($query) {
            $query->where('status_id', 4)
                ->orWhere('status_id', 12);
        })
        ->with(['latestApplication.workPost.office.company', 'status']) // Include the latest application for each student
        ->get();
        
        // Transformed Students
        $transformedStudents = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'studentNo' => $student->id,
                'name' => $student->user ? $this->getFullName(
                    $student->user->first_name ?? "",
                    $student->user->middle_name ?? "",
                    $student->user->last_name ?? "",
                ) : "No name", // Assuming you have an accessor for the full name
                'company' => $student->latestApplication->workPost->office->company->name,
                'status' => $student->status->name,
            ];
        });

    // Optionally transform students with applications, if needed
    return response()->json($transformedStudents, 200);
}

}
