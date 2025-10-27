<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeanProgramRequest;
use App\Models\College;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeanProgramController extends Controller
{

    /**
     * A public function that updates a program by ID
     * @param \App\Http\Requests\DeanProgramRequest $request
     * @param string $program_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateProgramById(DeanProgramRequest $request, String $program_id) {
         // Get authenticated user
         $auth = Auth::user();

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Get the college associated with the authenticated user
        $college = College::where('dean_id', $auth->id)->first();
        // Check if the college exists
        if (!$college) {
            return response()->json(['message' => 'College not found. Unauthorized.'], 404);
        }

        // Find program by college_id and program_id
        $program = Program::where('college_id', $college['id'])->find($program_id);
        // Check if program exist
        if(!$program) {
            return response()->json(['message' => "Program not found."], 404);
        }

        // Update program
        $program->name = $validatedCredentials['name'];
        $program->save();

         // Transform program
         $transformedProgram = $this->getAndTransformProgram($program['id']);

        // Return programs
        return response()->json(['message' => "A program is updated.", "data" => $transformedProgram], 201);
    }

    /**
     * A private function that gets the program by ID
     * @param string $program_id
     * @return array
     */
    private function getAndTransformProgram(String $program_id) {
        // Find program by ID
        $program = Program::with(['students', 'coordinators', 'chairperson'])->find($program_id);

        // Transform program
        $transformedProgram = $this->transform($program);

        return $transformedProgram;
    }

    /**
     * A publuc function that creates a new record of program
     * @param \App\Http\Requests\DeanProgramRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewProgram(DeanProgramRequest $request) {
        // Get authenticated user
        $auth = Auth::user();
        
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Get the college associated with the authenticated user
        $college = College::where('dean_id', $auth->id)->first();

         // Check if the college exists
         if (!$college) {
            return response()->json(['message' => 'College not found. Unauthorized.'], 404);
        }

        // Create new program record
        $program = Program::create([
            "college_id" => $college['id'],
            "name" => $validatedCredentials['name']
        ]);
        // Check if program is created
        if(!$program) {
            return response()->json(['message' => "Unable to create new program."], 404);
        }

       // Transform program
        $transformedProgram = $this->getAndTransformProgram($program['id']);

        // Return programs
        return response()->json(['message' => "A program is created.", "data" => $transformedProgram], 201);
    }

    /**
     * A program that gets all the record of programs based on his assigned college including the total of students, and total of coordinators in each program.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllPrograms() {
        // Get authenticated user
        $auth = Auth::user();
    
        // Get the college associated with the authenticated user
        $college =  College::where('dean_id', $auth->id)->first();
    
        // Check if the college exists
        if (!$college) {
            return response()->json(['message' => 'College not found. Unauthorized.'], 404);
        }
    
        // Find all programs for the college
        // We also load the `college` relationship using eager loading
        $programs = Program::with(['students', 'coordinators', 'chairperson'])->where('college_id', $college->id)
        ->get();
    
        // Transform the programs to include student and coordinator counts
        $transformedPrograms = $programs->map(function ($program) {
            return $this->transform($program);
        });

        // Return the programs with a success message
        return response()->json( $transformedPrograms, 200);
    }

    /**
     * A private function that transforms and return attributes that are necessary.
     * @param mixed $program
     * @return array
     */
    private function transform($program) {
        return [
            "id" => $program['id'],
            "name" => $program['name'],
            "chairperson_assigned" => $program->chairperson 
                ? trim($program->chairperson->first_name . ' ' . $program->chairperson->middle_name . ' ' . $program->chairperson->last_name)
                : '', // If there's no chairperson, return an empty string
            "total_students" => $program->students ? $program->students->count() : 0,
            "total_coordinators" => $program->coordinators ? $program->coordinators->count() : 0,
            "created_at" => $program->created_at ? $this->formatDate($program->created_at) : "",
            "updated_at" => $program->updated_at ? $this->formatDate($program->updated_at) : "",
        ];
        
    }
}
