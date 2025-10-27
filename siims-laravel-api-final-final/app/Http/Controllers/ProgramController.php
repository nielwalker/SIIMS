<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\College;
use App\Models\Log;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as FacadesLog;

class ProgramController extends Controller
{

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * ProgramController constructor.
     */
    public function __construct()
    {
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    /**
     * Summary of getAllProgramsByDean: A public function that gets all Programs by ID.
     * @return JsonResponse|mixed
     */
    public function getAllProgramsByDean()
    {

        // Find College
        $college = College::where('dean_id', $this->user->id)->first();

        // Check if there is a college
        if (!$college) {
            return response()->json(['message' => 'College not found.']);
        }

        // Find all Programs by College
        $programs = Program::where('college_id', $college->id)->get();

        // Return response with status 200
        return response()->json($programs, 200);
    }

    private function findProgram(String $program_id)
    {


        // Find Program
        $program = Program::find($program_id);

        // Check if program does not exist
        if (!$program) {
            throw new \App\Exceptions\ProgramNotFoundException();
        }

        // Return Program
        return $program;
    }

    /**
     * Summary of getAllListsOfPrograms: A public function that gets all list of programs
     * @return JsonResponse
     */
    public function getAllListsOfPrograms()
    {

        // Get authenticated user
        $user = Auth::user();

        // Declare variable 'programLists'
        $programLists = null;

        // ! Check if role is Admin
        if ($user->hasRole('admin')) {
            // Get all programs
            $programLists = Program::all();
        }

        // ! Check if role is Dean
        else if ($user->hasRole('dean')) {
            
            // Find College
            $college = College::where('dean_id', $user->id)->first();

            // Check if there is a college
            if (!$college) {
                return response()->json(['message' => 'College not found.']);
            }

            // Get all programs by College Id
            $programLists = Program::where('college_id', $college->id)->get();
        }

        // ! Check if role is Chairperson
        else if ($user->hasRole('chairperson')) {
            // Expose all programs so chairperson can assign coordinators
            $programLists = Program::all();
        }



        // Transform Programs
        $transformedProgramLists = $programLists->map(function ($program) {
            return [
                "id" => $program->id,
                "name" => $program->name,
                "chairperson_id" => $program->chairperson_id ? "Occupied" : null
            ];
        });

        // Return Programs
        return $this->jsonResponse($transformedProgramLists);
    }

    /**
     * Summary of updateProgramChairpersonID: A public function that updates a Program's Chairperson ID
     * @param int $chairperson_id
     * @param int $program_id
     * @return void
     */
    public function updateProgramChairpersonID(int $chairperson_id, int $program_id)
    {

        // Find Program
        $program = $this->findProgram($program_id);

        // Update and Save Chairperson ID
        $program->chairperson_id = $chairperson_id;
        $program->save();
    }

    /**
     * Summary of deleteProgram: A public function that deletes a Program.
     * @param string $program_id
     * @return JsonResponse|mixed
     */
    public function deleteProgram(String $program_id)
    {

        // Find Program by ID
        $program = $this->findProgram($program_id);

        // Check if Program has a Students
        if ($program->students()->exists()) {
            return response()->json(['message' => 'The program cannot be deleted because it still has associated students.'], 409);
        }

        // Check if the Program has a Coordinators
        if ($program->coordinators()->exists()) {
            return response()->json(['message' => 'The program cannot be deleted because it still has associated coordinators.'], 409);
        }

        // Delete Program
        $program->delete();

        // Return response with status 201
        return $this->jsonResponse(['message' => "Program is deleted."], 201);
    }


    /**
     * Summary of updateProgramById: A public function that updates a Program by ID.
     * @param \App\Http\Requests\ProgramRequest $request
     * @param string $program_id
     * @return JsonResponse|mixed
     */
    public function updateProgramById(ProgramRequest $request, String $program_id)
    {
        // Get validated
        $validated = $request->validated();

        // Find Program
        $program = Program::find($program_id);

        /**
         * Search if there is an existing Chairperson in the Program
         */
        if (isset($validated['chairperson_id'])) {
            $chairperson = User::find($validated['chairperson_id']);
            $existingChairpersonProgram = Program::where('chairperson_id', $validated['chairperson_id'])
                ->where('id', '!=', value: $program_id)
                ->first();

            if ($existingChairpersonProgram) {
                return response()->json(['message' => "This chairperson is already assigned to another program."], 400);
            }

            $program->chairperson_id = $validated['chairperson_id'];
        }

        // Update College
        $program->name = $validated['name'];
        $program->save();

        // Return response with status 201
        return $this->jsonResponse(['message' => 'Program is updated.', 'data' => $this->getProgram($program->id)], 201);
    }

    /**
     * Summary of findProgramById: Find Program by program_id.
     * @param string $program_id
     * @return Program|\Illuminate\Database\Eloquent\Collection|null
     */
    private function findProgramById(String $program_id)
    {

        // Check if User Role has Admin
        if ($this->user->hasRole('admin')) {
            $program = Program::with(['college', 'chairperson'])->withCount(['students', 'coordinators'])->find($program_id);
        }

        // Other Roles
        return Program::with(['students', 'coordinators', 'chairperson'])->withCount(relations: ['students', 'coordinators'])->find($program_id);
    }

    /**
     * Summary of getAllProgramsForDean: A private function that gets all Programs for the Dean.
     * @return JsonResponse|mixed
     */
    private function getAllProgramsForDean()
    {
        // Get the College associated with the authenticated User
        $college =  College::where('dean_id', $this->user->id)->first();

        // Check if the College exists
        if (!$college) {

            return $this->jsonResponse(['message' => 'College not found. Unauthorized.'], 404);
        }

        // Get all Programs including their College
        $programs = Program::with(['students', 'coordinators', 'chairperson'])->where('college_id', $college->id)->withCount(relations: ['students', 'coordinators']);

        // Return Programs
        return $programs;
    }

    /**
     * Summary of getAllProgramsForAdmin: A private function that gets all Programs for the Admin.
     * @return JsonResponse|mixed
     */
    private function getAllProgramsForAdmin()
    {
        // Get all Programs including their College
        $programs = Program::withTrashed()->with(['college', 'chairperson'])->withCount(['students', 'coordinators']);

        // Return response with status 200
        return $programs;
    }

    /**
     * Summary of getProgram: A private function that gets a Program by ID
     * @param string $program_id
     * @return ProgramResource
     */
    private function getProgram(String $program_id)
    {
        // Declare variable program
        $program = new Program();

        /**
         * Check Roles
         */
        // For Admin
        if ($this->user->hasRole('admin')) {
            // Get Program by ID
            $program = Program::with(['college', 'chairperson'])->withCount(['students', 'coordinators'])->find($program_id);
        }

        // For Dean
        else {
            // Get all Programs including their College
            $program = Program::with(['students', 'coordinators', 'chairperson'])->withCount(relations: ['students', 'coordinators'])->find($program_id);
        }

        // Return collection
        //  FacadesLog::info('Search Term: ', ['term' => new ProgramResource($program)]);  // Log the search term on the backend

        return new ProgramResource($program);
    }

    /**
     * Summary of getAllPrograms: A public function that gets all Programs
     * @return JsonResponse|mixed
     */
    public function getAllPrograms(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Initialize Query Variable variable
        $query = null;

        /**
         * Check if User has Role of Admin
         */
        if ($this->user->hasRole('admin')) {
            // Call method
            $query = $this->getAllProgramsForAdmin();
        }

        /**
         * Check if User has Role of Dean
         */
        else if ($this->user->hasRole('dean')) {
            // Call method
            $query = $this->getAllProgramsForDean();
        }
        /**
         * Check if User has Role of Chairperson
         * - For now, expose all programs similarly to admin to allow assignment
         */
        else if ($this->user->hasRole('chairperson')) {
            $query = Program::query()->with(['college', 'chairperson'])->withCount(['students', 'coordinators']);
        }

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $programs = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $programsResources = ProgramResource::collection($programs);

        // Store the login success log in the database
        Log::create([
            'user_id' => $this->user->id,
            'action_type' => 'View',
            'entity' => 'Program',
            'entity_id' => $this->user->id,
            'description' => "User {$this->user->id} views programs successfully.",
            'status' => 'Success',
            'http_code' => 200,
            'ip_address' => request()->ip(),
        ]);

        // Return Program Resources
        return $programsResources;
    }

    /**
     * Summary of addNewProgram: A public function that creates a new Program record.
     * @param \App\Http\Requests\ProgramRequest $request
     * @return JsonResponse|mixed
     */
    public function addNewProgram(ProgramRequest $request)
    {

        // Get validated
        $validated = $request->validated();

        // Empty Program variable
        $program = "";

        /**
         * FOR ADMIN
         * - Check if the User has Role of Admin
         */
        if ($this->user->hasRole('admin')) {
            // Create a new Program record
            $program = Program::create($validated);
        }

        /**
         * FOR DEAN
         * - Check if the User has Role of Dean
         */
        else if ($this->user->hasRole('dean')) {

            // Find User in the College
            $college = College::where('dean_id', $this->user->id)->first();

            // Check if dean_id does exist
            if (!$college->dean_id) {
                return response()->json(['message' => 'College not found. Unauthorized'], 403);
            }

            // Create a new Program record
            $program = Program::create([
                'college_id' => $college->id,
                'name' => $validated['name'],
            ]);
        }
        /**
         * FOR CHAIRPERSON
         */
        else if ($this->user->hasRole('chairperson')) {
            // Create a new Program record without binding to a specific college (or adapt as needed)
            $program = Program::create([
                'name' => $validated['name'],
            ]);
        }

        // Return Response with status 201
        return $this->jsonResponse([
            'message' => 'A new program is created',
            'data' => $this->getProgram($program->id),
        ], 201);
    }

    // Get all programs by college id
    public function getAllProgramsByCollegeId(String $college_id)
    {

        $programs = Program::where('college_id', operator: $college_id)->get();

        return $programs;
    }

    // Find a program
    public function find(string $program_id)
    {

        // Find program by id
        $program = Program::find($program_id);

        // Check if program exist
        if (!$program) {
            return response()->json([
                'error' => 'Program not found.'
            ], 404);
        }

        // Return program
        return $program;
    }

    // Stores a program record
    public function store(ProgramRequest $request)
    {

        // Retrieve validated data
        $validated = $request->validated();

        // Insert new record
        Program::insert($validated);

        // Return successful insertion
        return response()->json([
            'success' => 'The program is created.'
        ]);
    }

    // Update a program
    public function update(ProgramRequest $request, string $program_id)
    {

        // Find program by id
        $program = $this->find($program_id);

        // Check if program exist
        if ($program instanceof JsonResponse) {
            return $program; // Return 404 not found
        }

        // Validate
        $validated = $request->validated();

        // Update program model
        $program->college_id = $validated['college_id'];
        $program->dept_office_id = $validated['dept_office_id'];
        $program->name = $validated['name'];
        $program->max_internships = $validated['max_internships'];

        // Save program 
        $program->save();

        // Return successful update
        return response()->json([
            'success' => 'The program is updated.'
        ]);
    }

    // Delete program record
    public function destroy(string $program_id)
    {

        // Find program record by ID
        $program = $this->find($program_id);

        // Check if program exist
        if ($program instanceof JsonResponse) {
            return $program; // Return the 404 response if not found
        }

        // Delete program
        $program->delete();

        // Return successful delete
        return response()->json([
            'success' => 'The program has been deleted.'
        ]);
    }
}
