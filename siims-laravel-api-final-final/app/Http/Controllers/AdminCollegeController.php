<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCollegeRequest;
use App\Models\College;
use App\Models\User;
use App\Models\Program;  // Assuming you have a Program model
use App\Services\ActionLogger;
use Illuminate\Http\Request;

class AdminCollegeController extends Controller
{
    /**
     * A function that gets all the users with the role of dean, including their assigned college if any.
     */
    public function getDeansWithorWithoutCollegeAssigned()
    {
        // Fetch all users with the role of 'dean'
        $deans = User::whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->with('college')->get();

        // Transform the response to include dean and college details
        $deansWithColleges = $deans->map(function ($dean) {
            return [
                'id' => $dean->id,
                'name' => trim("{$dean->first_name} {$dean->middle_name} {$dean->last_name}"),
                'email' => $dean->email,
                'phone_number' => $dean->phone_number,
                'college' => $dean->college ? [
                    'id' => $dean->college->id,
                    'name' => $dean->college->name,
                ] : 'Not assigned', // Return "Not assigned" if no college is linked
            ];
        });

        // Return the transformed data
        return response()->json($deansWithColleges);
    }

    // Transform College (Including Program Count and Dean)
    private function transform($college)
    {
        return [
            "id" => $college['id'],
            "name" => $college['name'],
            "dean_id" => $college['dean_id'],
            'dean_name' => $college->dean ? 
                trim("{$college->dean->first_name} {$college->dean->middle_name} {$college->dean->last_name}") : 
                'No dean is assigned',
            "program_count" => $college->programs()->count(), // Count the programs
            "created_at" => $this->formatDate($college['created_at']),
            "updated_at" => $this->formatDate($college['updated_at']),
        ];
    }

    // Fetch and Transform Colleges (Including Program Count and Dean)
    private function fetchColleges()
    {
        $colleges = College::with('dean', 'programs')->get(); // Eager load 'programs' relationship
        if (!$colleges) {
            return response()->json(['message' => 'Colleges not found.'], 404);
        }

        return $colleges->map(function ($college) {
            return $this->transform($college);
        });
    }

    // Unified Response Method (visibility must be compatible with base Controller)
    protected function jsonResponse($data, int $status = 200, array $headers = []): \Illuminate\Http\JsonResponse
    {
        return response()->json($data, $status, $headers);
    }

    // Update College
    public function updateCollegeById(AdminCollegeRequest $request, String $college_id, ActionLogger $actionLogger)
    {
        $validatedCredentials = $request->validated();
        $college = College::find($college_id);

        if (!$college) {
            $actionLogger->logAction(
                'Update',
                'College',
                $college_id,
                "Unable to update a college because it was not found.",
                'Failed',
                "404"
            );

            return $this->jsonResponse("A college is not found.", null, 404);
        }

        if (isset($validatedCredentials['dean_id'])) {
            $dean = User::find($validatedCredentials['dean_id']);
            if (!$dean) {
                return $this->jsonResponse("The specified dean does not exist.", null, 400);
            }

            $existingDeanCollege = College::where('dean_id', $validatedCredentials['dean_id'])
                ->where('id', '!=', $college_id)
                ->first();

            if ($existingDeanCollege) {
                return $this->jsonResponse("This dean is already assigned to another college.", null, 400);
            }

            $college->dean_id = $validatedCredentials['dean_id'];
        }

        $college->name = $validatedCredentials['name'];
        $college->save();

        $actionLogger->logAction(
            'Update',
            'College',
            $college->id,
            "College updated successfully.",
            'Success',
            "201"
        );

        // Get the updated deans and transform
        $transformCollege = $this->transform($college);

        return response()->json(["message" => "The college has been updated.", "data" => $transformCollege], 201);
        
    }

    // Add New College
    public function addNewCollege(AdminCollegeRequest $request, ActionLogger $actionLogger)
    {
        $validatedCredentials = $request->validated();
        $college = College::create($validatedCredentials);

        if (!$college) {
            $actionLogger->logAction(
                'Create',
                'College',
                0,
                "Unable to create a new college",
                'Failed',
                "404"
            );

            return $this->jsonResponse("The college is not created.", null, 400);
        }

        $actionLogger->logAction(
            'Create',
            'College',
            $college->id,
            "Created a new college",
            'Success',
            "201"
        );

        // Get college and transform
        $transformCollege = $this->transform($college);

        return response()->json(["message" => "The college is created. Newly added colleges fetched successfully.", "data" => $transformCollege], 201);
    }

    

    // Get All Colleges
    public function getAllColleges(ActionLogger $actionLogger)
    {
        // Get all colleges
        $colleges = College::all();

        // Check if colleges does exist
        if(!$colleges) {
            return response()->json(['message' => 'Colleges not found.'], 404);
        }

        // Transform colleges
        $transformedColleges = $colleges->map(function ($college) {
            return $this->transform($college);
        });

        // Get all deans listers
        $deans = $this->getDeansWithorWithoutCollegeAssigned();

        // Return colleges
        return response()->json([
            "initial_colleges" => $transformedColleges,
            "list_of_deans" => $deans->original,
        ], 200);

    }
}
