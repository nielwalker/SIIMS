<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentEducationRequest;
use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentEducationController extends Controller
{
    // A private function that compares the start date and end date
    private function compareStartAndEndDate($dateCredentials)
    {
        // Compare the start date and end date. End date should not be before the start date.
        return $dateCredentials['start_date'] >= $dateCredentials['end_date'];
    }

    // A public function that views the list of educations by Student ID
    public function getAllEducations()
    {
        // Get authenticated users
        $user = Auth::user()->student;

        // Get all educations by student id and sort by date
        $educations = Education::where('student_id', $user['id'])->get();

        // Return status 200 and a list of educations
        return response()->json($educations);
    }

    // A public function that deletes a record of education by ID
    public function deleteEducationById(String $education_id)
    {
        // Find education by ID
        $education = Education::find($education_id);
        if (!$education) {
            return response()->json(['message' => 'Education not found.'], 404);
        }

        // Delete education
        $education->delete();

        // Return status 200 indicating a successful deletion.
        return response()->json(['message' => 'Education is deleted.'], 200);
    }

    // A public function that updates a record of education by ID
    public function updateEducationById(StudentEducationRequest $request, String $education_id)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find education by ID
        $education = Education::find($education_id);
        if (!$education) {
            return response()->json(['message' => 'Education not found.'], 404);
        }

        // Compare the start date and end date. End date should not be before the start date.
        if ($this->compareStartAndEndDate($validatedCredentials)) {
            // If the end date is before the start date, return an error response
            return response()->json(['message' => 'The end date cannot be before the start date.'], 400);
        }

        // Mass update the work experience
        Education::where('id', $education['id'])->update([
            "school_name" => $validatedCredentials['school_name'],
            "full_address" => $validatedCredentials['full_address'],
            "start_date" => $validatedCredentials['start_date'],
            "end_date" => $validatedCredentials['end_date'],
        ]);

        // Return 200 status correct
        return response()->json(['message' => 'Education is updated.'], 200);
    }

    // A public function that creates as new record of education
    public function createNewEducation(StudentEducationRequest $request)
    {
        // Get authenticated user
        $user = Auth::user()->student;
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Compare the start date and end date. End date should not be before the start date.
        if ($this->compareStartAndEndDate($validatedCredentials)) {
            // If the end date is before the start date, return an error response
            return response()->json(['message' => 'The end date cannot be before the start date.'], 400);
        }

        // Merge student_id in the validated_credentials
        $validatedCredentials['student_id'] = $user->id;

        // Create new education record
        $education = Education::create($validatedCredentials);
        // Check if the record is created
        if (!$education) {
            return response()->json(['message' => 'An education is not created.'], 400);
        }

        // Return status 201 indicating a successful creation
        return response()->json(['message' => 'An education is created.'], 200);
    }
}
