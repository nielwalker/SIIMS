<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChairpersonRequest;
use App\Http\Requests\StudentAssignRequest;
use App\Http\Resources\ChairpersonResource;
use App\Models\Coordinator;
use App\Models\Program;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChairpersonController extends UserController
{
    /**
     * The program controller
     */
    private $programController;

    /**
     * The coordinator controller
     */
    private $coordinatorController;

    /**
     * The user role controller
     */
    private $userRoleController;

    // Log Controller
    private $logController;

    /**
     * ChairpersonController constructor.
     */
    public function __construct(ProgramController $programController, CoordinatorController $coordinatorController, UserRoleController $userRoleController, LogController $logController)
    {
        $this->logController = $logController;
        $this->programController = $programController;
        $this->coordinatorController = $coordinatorController;
        $this->userRoleController = $userRoleController;
    }

    /**
     * Summary of findChairperson: A public function that finds a Chairperson by ID
     * @param string $chairperson_id
     * @return User|\Illuminate\Database\Eloquent\Collection|null
     */
    public function findChairperson(string $chairperson_id)
    {
        // Get only user that has Role of Chairpersons
        $chairperson = User::whereHas('roles', function ($query) {
            $query->where('name', 'chairperson');
        })->with(['program.college'])->find($chairperson_id);

         // Check if dean does not exist.
         if(!$chairperson) {
            abort(404, 'Chairperson not found.');
        }


        return $chairperson;
    }

    /**
     * Summary of updateChairpersonById: A public function that updates a Chairperson by ID
     * @param \App\Http\Requests\ChairpersonRequest $request
     * @param string $chairperson_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateChairpersonById(ChairpersonRequest $request, String $chairperson_id)
    {

        // Get validated 
        $validated = $request->validated();

        // Find Chairperson ID
        $chairperson = $this->findChairperson($chairperson_id);

        // Update and Save Chairperson
        $chairperson->update($validated);
        $chairperson->save();

        // Add new log
        $this->logController->addNewLog(
            'Updated a user (chairperson)',
            'User (Chairperson)',
            $chairperson->id,
            'updates a user (chairperson).',
            '200',
        );

        // Return resource
        return $this->jsonResponse([
            "message" => 'A chairperson is updated.',
            'data' => new ChairpersonResource($chairperson)
        ], 201);
    }

    /**
     * Summary of deleteChairperson: A public function that deletes a Chairperson by ID
     * @param string $chairperson_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteChairperson(String $chairperson_id)
    {

        // Find Chairperson
        $chairperson = $this->findChairperson($chairperson_id);

        // Delete Chairperson
        $chairperson->delete();

        // Add new log
        $this->logController->addNewLog(
            'Deletes a dean',
            'Dean',
            'N/A',
            'attempts to delete a dean.',
            '200',
        );

        // Return 
        return $this->jsonResponse([
            'message' => 'A chairperson is deleted'
        ], 201);
    }

    /**
     * Summary of addNewChairperson: A public function that adds new Chairperson.
     * @param \App\Http\Requests\ChairpersonRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewChairperson(ChairpersonRequest $request)
    {
        // Get validated
        $validated = $request->validated();

        // Create a new User
        $user = $this->addNewUser($validated);

        // Assign Chairperson to a Program
        if ($validated['program_id']) {
            $this->programController->updateProgramChairpersonID($user->id, $validated['program_id']);
        }

        /**
         * Check if $validated[allow_coordinator] is true then create also an coordinator
         */
        if ($validated['allow_coordinator']) {

            // Create Coordinator
            $this->coordinatorController->addCoordinator($user->id, $validated['program_id']);

            // Create User Role
            $this->userRoleController->addUserRole($user->id, 3); // Coordinator
           
        }

        // Create User Role
        $this->userRoleController->addUserRole($user->id, 2); // Chairperson


        // Add new log
        $this->logController->addNewLog(
            'Created a user (chairperson)',
            'User (Chairperson)',
            $user->id,
            'adds a user (chairperson).',
            '201',
        );

        // Return resource
        return $this->jsonResponse([
            "message" => 'A new chairperson is created.',
            "data" => new ChairpersonResource($this->findChairperson($user->id)),
        ], 201);
    }

    /**
     * Summary of getChairpersonCurrentProgramId: A public function that gets the current program 'ID' of a Chairperson
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getChairpersonCurrentProgramId()
    {

        // Get auth User
        $user = Auth::user();

        // Find Program by ID
        $program = Program::where('chairperson_id', $user->id)->first();

        return response()->json($program->id, 200);
    }

    public function assignStudents(StudentAssignRequest $request)
    {

        // Get validated 
        $validated = $request->validated();
    }

    /**
     * Summary of getAllChairpersons: A public function that gets all Chairpersons.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllChairpersons(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Get All User (Dean) Info
        $query = $this->fetchAllUsers();

        // Get only user that has Role of Chairpersons
        $query = $query->whereHas('roles', function ($query) {
            $query->where('name', 'chairperson');
        })->with(['program.college']);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $chairpersons = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $chairpersonsResources = ChairpersonResource::collection($chairpersons);

         // Add new log
         $this->logController->addNewLog(
            'Views the list of chairpersons',
            'Chairperson',
            'N/A',
            'attempts to view the list of chairpersons.',
            '200',
        );

        // Get all Chairpersons
        return $chairpersonsResources;
    }

    /**
     * Summary of getAllChairpersonsIncludingProgram: A public function that gets all Chairpersons including their assigned Program.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllChairpersonsIncludingProgram()
    {

        // Get auth User
        $user = Auth::user();

        // Initialize chairpersons variable
        $chairpersons = null;

        // ! FOR ADMIN
        if ($user->hasRole('admin')) {

            // Get all User that has a Role of Chairperson
            $chairpersons = User::whereHas('roles', function ($query) {
                $query->where('name', 'chairperson');
            })->with(['program'])->get();
        }

        // ! FOR DEAN
        else {

            // Get all User that has a Role of Chairperson
            $chairpersons = User::whereHas('roles', function ($query) {
                $query->where('name', 'chairperson');
            })->with(['program'])->whereHas('program.college', function ($query) use ($user) {
                $query->where('dean_id', $user->id);
            })->get();
        }



        // Transform Chairperson's attributes
        $transformedChairpersons = $chairpersons->map(function ($chairperson) {

            return [
                "id" => $chairperson->id,
                "name" => $this->getFullName($chairperson->first_name, $chairperson->middle_name ?? "", $chairperson->last_name),
                "college_id" => $chairperson->program ? $chairperson->program->college_id : "No ID",
                "program" => $chairperson->program ? $chairperson->program->name : "No Program assigned"
            ];
        });

        // Return response with status 200
        return response()->json($transformedChairpersons, 200);
    }
}
