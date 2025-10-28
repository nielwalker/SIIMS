<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentProfileController extends ProfileController
{
    /**
     * Work Experiencce and Education Controller
     */
    private $workExperienceController;
    private $educationController;

    public function __construct(WorkExperienceController $workExperienceController, EducationController $educationController)
    {
        $this->workExperienceController = $workExperienceController;
        $this->educationController = $educationController;
    }

    /**
     * Summary of getProfile: A public function thta gets the Profile of the Student
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        // Load authenticated user with nullable-safe relations
        $authUser = Auth::user()->load([
            'student.company',
            'student.coordinator', // coordinator may already be a User model
            'student.program.college',
            'student.program.chairperson', // chairperson may already be a User model
        ]);

        $student = $authUser->student; // may be null

        // Build a robust payload that tolerates missing relations
        $payload = [
            'user' => [
                'id' => $authUser->id,
                'first_name' => $authUser->first_name,
                'last_name' => $authUser->last_name,
                'email' => $authUser->email,
            ],
            'student' => $student ? [
                'company' => $student->company ? [
                    'name' => $student->company->name,
                ] : null,
                'coordinator' => $student->coordinator ? (function() use ($student) {
                    $coord = $student->coordinator;
                    // If model has a user() relation, use it, else assume it's already a User
                    $coordUser = method_exists($coord, 'user') ? optional($coord->user) : optional($coord);
                    return [
                        'user' => [
                            'first_name' => $coordUser->first_name,
                            'last_name' => $coordUser->last_name,
                        ],
                    ];
                })() : null,
                'program' => $student->program ? [
                    'name' => $student->program->name,
                    'college' => [ 'name' => optional($student->program->college)->name ],
                    'chairperson' => $student->program->chairperson ? (function() use ($student) {
                        $chair = $student->program->chairperson;
                        $chairUser = method_exists($chair, 'user') ? optional($chair->user) : optional($chair);
                        return [
                            'user' => [
                                'first_name' => $chairUser->first_name,
                                'last_name' => $chairUser->last_name,
                            ],
                        ];
                    })() : null,
                ] : null,
            ] : null,
        ];

        // Also include education/work experience lists (optional)
        $payload['educations'] = $this->educationController->getAllEducations()->toArray(request());
        $payload['work_experiences'] = $this->workExperienceController->getAllWorkExperiences()->toArray(request());
        $payload['certificates'] = Certificate::where('student_id', $authUser->id)->get();

        return $this->jsonResponse($payload, 200);
    }
}
