<?php

namespace App\Http\Controllers;

use App\Http\Requests\EducationRequest;
use App\Http\Resources\EducationResource;
use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EducationController extends Controller
{

    /**
     * Summary of findEducation: A private function that finds an Education by ID.
     * @param string $education_id
     * @return TModel|\Illuminate\Database\Eloquent\Collection|null
     */
    private function findEducation(String $education_id)
    {
        // Find Education
        $education = Education::find($education_id);


        return $education;
    }

    /**
     * Summary of addNewEducation: A public function that adds a new Education.
     * @param \App\Http\Requests\EducationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewEducation(EducationRequest $request)
    {

        // Get authenticated user
        $auth = Auth::user();

        // Get validated
        $validated = $request->validated();

        // Mass Assignment Education
        $education = Education::create(array_merge(
            [
                'student_id' => $auth->id,
            ],
            $validated
        ));

        // Return with status 201
        return $this->jsonResponse([
            'message' => 'An education is created.',
            'data' => new EducationResource($education)
        ], 201);
    }

    /**
     * Summary of deleteEducation: A public function that deletes an Education by ID.
     * @param string $education_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteEducation(String $education_id)
    {

        // Find Education
        $education = $this->findEducation($education_id);

        // Delete 
        $education->delete();

        // Return response
        return $this->jsonResponse([
            'message' => "An education is deleted"
        ], 201);
    }

    /**
     * Summary of updateEducation: A public function that updates an Education Record by ID
     * @param \App\Http\Requests\EducationRequest $request
     * @param string $education_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEducation(EducationRequest $request, String $education_id)
    {

        // Get validated
        $validated = $request->validated();

        // Find Education
        $education = $this->findEducation($education_id);

        // Update and save Education
        $education->update($validated);
        $education->save();

        // Return with status 201
        return $this->jsonResponse([
            'message' => 'An education is created.',
            'data' => new EducationResource($education)
        ], 201);
    }

    /**
     * Summary of getAllEducations: A public function that gets all the Education records.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllEducations()
    {

        // Get authenticated User
        $auth = Auth::user();

        if ($auth->hasRole('admin')) {
            return $this->jsonResponse([
                'message' => "Under construction..."
            ], 404);
        }

        // Find Student's Education
        return EducationResource::collection($auth->student->educations);
    }
}
