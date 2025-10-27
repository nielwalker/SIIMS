<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminProgramRequest;
use App\Models\College;
use App\Models\Program;
use App\Models\User;

class AdminProgramController extends Controller
{

    /**
     * A public function that deletes a program by ID
     * @param string $program_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function deleteProgramById(String $program_id)
    {
        // Get program
        $program = Program::find($program_id);

        // Check if program exists
        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }

        // Delete program
        $program->delete();

        // Return programs
        return response()->json(['message' => 'Program deleted successfully.'], 201);
    }

    /**
     * A public function that updates a program by ID
     * @param \App\Http\Requests\AdminProgramRequest $request
     * @param string $program_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateProgramById(AdminProgramRequest $request, String $program_id)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Get program
        $program = Program::find($program_id);

        // Check if program exists
        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }

        // Get chairperson_id from the request
        $chairpersonId = $validatedCredentials['chairperson_id'] ?? null;

        // If chairperson_id is provided, check for conflicts
        if ($chairpersonId) {
            // Query for programs where the chairperson is already assigned (excluding the current program)
            $existingChairpersonAssignment = Program::where('chairperson_id', $chairpersonId)
                ->where('id', '!=', $program_id)
                ->exists();

            if ($existingChairpersonAssignment) {
                return response()->json([
                    'message' => 'The chairperson is already assigned to another program. Cannot update.'
                ], 400);
            }
        }

        // Update the program fields
        $program->chairperson_id = $chairpersonId; // Can be null
        $program->college_id = $validatedCredentials['college_id'];
        $program->name = $validatedCredentials['name'];
        $program->save();

        // Get the updated program and transform it
        // Get the program with their associated college and chairperson
        $transformedProgram = $this->transform(Program::with(['college', 'chairperson', 'students', 'coordinators'])->find($program['id']));
        
        // Return success response
        return response()->json(['message' => 'Program updated successfully.', 'data' => $transformedProgram], 201);
    }



    /**
     * A public function that adds a new program record by assigning to a college by ID
     * @param \App\Http\Requests\AdminProgramRequest $request
     * @return void
     */
    public function addNewProgram(AdminProgramRequest $request)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Check if college does exist
        $college = College::find($validatedCredentials['college_id']);
        if (!$college) {
            return response()->json(['message' => 'College not found.']);
        }

        // Create a new record of program   
        $program = Program::create([
            'name' => $validatedCredentials['name'],
            'college_id' => $college['id'],
        ]);

        // Check if program is created
        if (!$program) {
            return response()->json(['message' => 'Unable to create a new program.'], 400);
        }

        // Get the created program and transform
        $transformedProgram = $this->transform($program);

        // Return records of programs
        return response()->json(['message' => 'A new program is created.', 'data' => $transformedProgram], 201);
    }

    // Get programs by College Id
    public function getAllProgramsByCollegeId(String $college_id)
    {

        // Get programs by college_id
        $programs = Program::where('college_id', $college_id)->get();

        // Check if programs exist
        if (!$programs) {
            return response()->json(["message" => "Programs not found..."], 404);
        }

        // Return
        return response()->json($programs, 200);
    }

    // Get all programs with their associated college and chairperson
    public function getAllPrograms()
    {
        // Get all programs with their associated college and chairperson
        $programs = Program::with(['college', 'chairperson', 'students', 'coordinators'])->get();

        if ($programs->isEmpty()) {
            return response()->json(['message' => 'Programs not found.'], 404);
        }

        // Map the programs to include required attributes
        $programs->transform(function ($program) {
            return $this->transform($program);
        });

        // Get all users who have the 'chairperson' role
        $chairpersons = User::whereHas('roles', function ($query) {
            $query->where('name', 'chairperson');
        })->with('program')->get();

        // Example of processing the results to check program status
        $chairpersonsWithProgramStatus = $chairpersons->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name, // Concatenate with spaces
                'program_assigned' => $user->program ? $user->program->name : 'No Program Assigned',
            ];
        });

        // Get all colleges
        $colleges = College::all();

        return response()->json([
            "initial_programs" => $programs,
            "list_of_chairpersons" => $chairpersonsWithProgramStatus,
            "list_of_colleges" => $colleges,
        ], 200);
    }

    // Transform function to return specific fields
    public function transform($program)
    {
        return [
            'id' => $program->id,
            'name' => $program->name,
            "chairperson_id" => $program->chairperson ? $program->chairperson->id : "",
            'chairperson_full_name' => $program->chairperson ?
                $program->chairperson->first_name . ' ' .
                $program->chairperson->middle_name . ' ' .
                $program->chairperson->last_name : null, // Check if chairperson exists
            'chairperson_email' => $program->chairperson ? $program->chairperson->email : null, // Check if chairperson exists
            'total_students' => $program->students ? $program->students->count() : 0,
            'total_coordinators' => $program->coordinators ? $program->coordinators->count() : 0,
            'college_id' => $program->college ? $program->college->id : null, // Check if college exists
            'college_name' => $program->college ? $program->college->name : null, // Check if college exists
            'created_at' => $this->formatDate($program->created_at),
            'updated_at' => $this->formatDate($program->updated_at),
        ];
    }
}
