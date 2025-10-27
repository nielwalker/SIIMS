<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkExperienceRequest;
use App\Http\Resources\WorkExperienceResource;
use App\Models\Student;
use App\Models\WorkExperience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkExperienceController extends Controller
{

    /**
     * Summary of findWorkExperience: A private function that finds a Work Experience by ID.
     * @param string $work_experience_id
     * @return TModel|\Illuminate\Database\Eloquent\Collection|null
     */
    private function findWorkExperience(String $work_experience_id) {

        // Find
        $workExperience = WorkExperience::find($work_experience_id);

        return $workExperience;

    }

    /**
     * Summary of getAllWorkExperiences: A public function that adds a new record of work experiences.
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllWorkExperiences()
    {

        // Get Auth User
        $user = Auth::user();

        // Check role if Admin
        if ($user->hasRole('admin')) {

            // Get all Work Experiences
            $workExperiences = WorkExperience::all();

            // Check if Work Experiences exist
            if (!$workExperiences) {
                return response()->json(['message' => 'Work Experiences not found.'], 404);
            }

            // Return Work Experiences with status 200
            return response()->json($workExperiences, 200);
        }

        // Get Student with Work Experiences
        $workExperiences = WorkExperience::where('student_id', $user->student->id)->get();

        // Check if Work Experiences exist
        if (!$workExperiences) {
            return response()->json(['message' => 'Work Experiences not found.'], 404);
        }

        // Return response with status 200
        return WorkExperienceResource::collection($workExperiences);
    }

    /**
     * Summary of addWorkExperience: A public function that adds a new work experience.
     * @param \App\Http\Requests\WorkExperienceRequest $request
     * @param string $student_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addWorkExperience(WorkExperienceRequest $request)
    {

        // Get authenticated user 
        $user = Auth::user();

        // Get validated 
        $validated = $request->validated();

        // Find Student
        $student = Student::find($user->id);

        // Check user
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        // Add Work Experience
        $workExperience = WorkExperience::create(array_merge(['student_id' => $user->id], $validated));

        // Return response and status 201
        return $this->jsonResponse([
            'message' => "A new work experience is created.",
            'data' => new WorkExperienceResource($workExperience)
        ], 201);
    }
    
    /**
     * Summary of updateWorkExperience: A public function that updates a Work Experience
     * @param \App\Http\Requests\WorkExperienceRequest $request
     * @param string $work_experience_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateWorkExperience(WorkExperienceRequest $request, String $work_experience_id) {

        // Get validated
        $validated = $request->validated();

        // Find Work Experience by ID
        $workExperience = $this->findWorkExperience($work_experience_id);

        // Update and Save Work Experience
        $workExperience->update($validated);
        $workExperience->save();

        // Return Work Experience
        return $this->jsonResponse([
            'message' => "A work experience is updated.",
            'data' => new WorkExperienceResource($workExperience
            )
        ], 201);
    }

    /**
     * Summary of deleteWorkExperience: A public function that deletes a Work Experience by ID.
     * @param string $work_experience_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteWorkExperience(String $work_experience_id) {

        // Find 
        $workExperience = $this->findWorkExperience($work_experience_id);

        // Delete Work Experience
        $workExperience->delete();

        // Return 
        return $this->jsonResponse([
            'message' => 'A work experience is deleted.'
        ],  201);

    }
    
}
