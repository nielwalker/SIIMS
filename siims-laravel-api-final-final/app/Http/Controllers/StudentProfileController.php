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

        // Get Student User Info
        $user = $this->getGeneralInfo();


        // Get Certificates
        $certificates = Certificate::where('student_id', $user->id)->get();

        // Merge Array Variable
        $mergedArray = [
            "program" => $user->student->program->name,
            "college" => $user->student->program->college->name,
            "educations" => $this->educationController->getAllEducations()->toArray(request()),
            "work_experiences" => $this->workExperienceController->getAllWorkExperiences()->toArray(request()),
            "certificates" => $certificates,
        ];

        // Merged Array
        $mergedUser = array_merge($user->toArray(request()), $mergedArray);

        // Return response
        return $this->jsonResponse($mergedUser, 200);
    }
}
