<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentWorkExperienceRequest;
use App\Http\Requests\WorkExperienceRequest;
use App\Models\WorkExperience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentWorkExperienceController extends Controller
{
   

    // A private function that finds a work experience by id
    private function findWorkExperienceById(String $work_experience_id) {
        // Find work experience
        $workExperience = WorkExperience::find($work_experience_id);
        if (!$workExperience) {
            return response()->json(['message' => 'Work experience not found.']);
        }

        // Return a work experience record
        return $workExperience;
    }

    // A private function that compares the start date and end date
    private function compareStartAndEndDate($dateCredentials)
    {
        // Compare the start date and end date. End date should not be before the start date.
        return $dateCredentials['start_date'] >= $dateCredentials['end_date'];
    }

    // A function that deletes a work experience by id
    public function deleteWorkExperienceById(String $work_experience_id) {
        // Find a work experience
        $workExperience = WorkExperience::find($work_experience_id);
        // Find work experience by id
        $workExperience = $this->findWorkExperienceById($work_experience_id);

        // Deletes a work experience record
        $workExperience->delete();

        // Return status 200 indicating successful deletion
        return response()->json(['message' => 'Work experience is deleted.']);
    }

    // A function that updates a work experience by id
    public function updateWorkExperienceById(StudentWorkExperienceRequest $request, String $work_experience_id)
    {

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find work experience
        $workExperience = $this->findWorkExperienceById($work_experience_id);

        // Compare the start date and end date. End date should not be before the start date.
        if($this->compareStartAndEndDate($validatedCredentials)) {
            // If the end date is before the start date, return an error response
            return response()->json(['message' => 'The end date cannot be before the start date.'], 400);
        }

          // Mass update the work experience
          WorkExperience::where('id', $workExperience['id'])->update([
            'job_position' => $validatedCredentials['job_position'],
            'company_name' => $validatedCredentials['company_name'],
            'full_address' => $validatedCredentials['full_address'],  
            'start_date' => $validatedCredentials['start_date'],
            'end_date' => $validatedCredentials['end_date'] 
        ]);

        // Return status 200 indicating successful update
        return response()->json(['message' => 'Work Experience is updated.'], 200);
    }

    // A function that fetch all work experience
    public function getAllWorkExperiences()
    {
        // Get the authenticated user
        $user = Auth::user()->student;

        // Get all work experiences by student id and sort by date
        $workExperiences = WorkExperience::where('student_id', $user['id'])->get();

        return response()->json($workExperiences);
    }

    // A function that creates a new record of work experience
    public function addNewWorkExperience(StudentWorkExperienceRequest $request)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Get the authenticated user
        $user = Auth::user()->student;

        // Compare the start date and end date. End date should not be before the start date.
        if($this->compareStartAndEndDate($validatedCredentials)) {
            // If the end date is before the start date, return an error response
            return response()->json(['message' => 'The end date cannot be before the start date.'], 400);
        }

        // Merge the student_id from the user
        $validatedCredentials['student_id'] = $user['id'];

        // Create a new record of work_experience
        $workExperience = WorkExperience::create($validatedCredentials);

        // Check if the record is created
        if (!$workExperience) {
            return response()->json(['message' => 'Cannot create a new work experience'], 400);
        }

        // Return status 201 indicating successful creation
        return response()->json(['message' => 'A new work experience is created'], 201);
    }
}
