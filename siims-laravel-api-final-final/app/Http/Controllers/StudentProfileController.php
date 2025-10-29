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
            'student.company.user',
            'student.company.offices',
            'student.coordinator', // coordinator may already be a User model
            'student.program.college',
            'student.program.chairperson', // chairperson may already be a User model
            // latest application with office and supervisor for address/name fallbacks
            'student.latestApplication.workPost.office.supervisor.user',
            'student.latestApplication.workPost.office.company',
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
                'company' => $student->company ? (function() use ($student) {
                    // Primary: company info from relation
                    $company = $student->company;
                    $result = [ 'name' => $company->name ];
                    // Try to derive address from the first office if company has offices
                    if ($company->relationLoaded('offices')) {
                        $firstOffice = optional($company->offices->first());
                        if ($firstOffice) {
                            $addrParts = array_filter([
                                $firstOffice->street,
                                $firstOffice->barangay,
                                $firstOffice->city_municipality,
                                $firstOffice->province,
                                $firstOffice->postal_code,
                            ]);
                            if (!empty($addrParts)) {
                                $result['address'] = implode(', ', $addrParts);
                            }
                        }
                    }
                    // Include company user (serves as supervisor/representative)
                    if ($company->relationLoaded('user') && $company->user) {
                        $result['user'] = [
                            'first_name'  => $company->user->first_name,
                            'middle_name' => $company->user->middle_name,
                            'last_name'   => $company->user->last_name,
                        ];
                    }
                    return $result;
                })() : null,
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
                // Add latest application details for address/supervisor fallbacks
                'latest_application' => $student->latestApplication ? (function() use ($student) {
                    $app = $student->latestApplication;
                    $office = optional(optional($app->workPost)->office);
                    $supervisorUser = optional(optional($office->supervisor)->user);
                    $addressParts = array_filter([
                        $office->street,
                        $office->barangay,
                        $office->city_municipality,
                        $office->province,
                        $office->postal_code,
                    ]);
                    return [
                        'office' => [
                            'address' => $addressParts ? implode(', ', $addressParts) : null,
                            'company' => [ 'name' => optional($office->company)->name ],
                            'supervisor' => [
                                'user' => [
                                    'first_name'  => $supervisorUser->first_name,
                                    'middle_name' => $supervisorUser->middle_name,
                                    'last_name'   => $supervisorUser->last_name,
                                ],
                            ],
                        ],
                    ];
                })() : null,
            ] : null,
        ];

        // Also include education/work experience lists (optional)
        $payload['educations'] = $this->educationController->getAllEducations()->toArray(request());
        $payload['work_experiences'] = $this->workExperienceController->getAllWorkExperiences()->toArray(request());
        $payload['certificates'] = Certificate::where('student_id', $authUser->id)->get();

        return $this->jsonResponse($payload, 200);
    }
}
