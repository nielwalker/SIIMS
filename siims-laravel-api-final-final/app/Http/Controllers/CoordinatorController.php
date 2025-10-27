<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoordinatorRequest;
use App\Http\Resources\CoordinatorResource;
use App\Models\College;
use App\Models\Coordinator;
use App\Models\Log;
use App\Models\Program;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoordinatorController extends UserController
{


    /**
     * The user role controller
     */
    private $userRoleController;

    /**
     * CoordinatorController constructor.
     */
    public function __construct(UserRoleController $userRoleController)
    {
        $this->userRoleController = $userRoleController;
    }

    /**
     * Summary of deleteCoordinatorById: A public function that deletes a Coordinator
     * @param string $coordinator_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function deleteCoordinatorById(String $coordinator_id)
    {

        // Find Coordinator and User
        $coordinator = Coordinator::find($coordinator_id);
        // $user = User::find($coordinator_id);

        // Search User Roles 
        //$userRoles = UserRole::where('user_id', $user->id)->get();


        // Delete Coordinator and User and roles
        $coordinator->delete();
        // $user->delete();
        // $userRoles->delete(); 

        // Return response with status 201
        return $this->jsonResponse(['message' => "Coordinator is deleted."], 201);
     
    }

    /**
     * Summary of getAllCoordinatorsByCollegeId: A public function that gets all Coordinators in the specific College.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllCoordinatorsByCollegeId()
    {

        // Find College
        $college = College::where('dean_id', $this->user->id)->first();

        // Check if college does exost
        if (!$college) {
            return response()->json(['message' => 'College not found']);
        }

        // Get Coordinators by program ID
        $coordinators = Coordinator::with(relations: ['user', 'program'])->whereHas('program', function ($query) use ($college) {
            $query->where('college_id', $college->id);
        })->withCount(['students'])->get();

        // Transform Coordinator's Attributes
        $transformedCoordinators = $coordinators->map(function ($coordinator) {
            return $this->transform($coordinator);
        });

        // Return response with status 200
        return response()->json($transformedCoordinators, 200);
    }

    /**
     * Summary of addCoordinator: A public function that creates a new Coordinator
     * @param string $id
     * @param string $program_id
     * @return void
     */
    public function addCoordinator(String $id, String $program_id)
    {

        // Create Coordinator
        Coordinator::create([
            'id' => $id,
            "user_id" => $id,
            "program_id" => $program_id,
        ]);
    }

    /**
     * Summary of addNewCoordinator: A public function that adds a new Coordinator
     * @param \App\Http\Requests\CoordinatorRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewCoordinator(CoordinatorRequest $request)
    {

        // Get validated
        $validated = $request->validated();

        // Create a new User
        $user = $this->addNewUser($validated);

        // Add New Coordinator
        if ($user) {

            // Add Coordinator
            $this->addCoordinator($user->id, $validated['program_id']);

            // Create User Role
            $this->userRoleController->addUserRole($user->id, 3);
        }

        // Return resource
        return $this->jsonResponse([
            "message" => 'A new coordinator is created.',
            "data" => new CoordinatorResource($this->findCoordinator($user->id)),
        ], 201);
    }

    /**
     * Summary of findCoordinator: A public function that finds a Coordinator by ID.
     * @param string $coordinator_id
     * @return Coordinator|\Illuminate\Database\Eloquent\Collection|null
     */
    public function findCoordinator(String $coordinator_id)
    {

        // Find Coordinator
        $coordinator = Coordinator::with(['user', 'program.college'])->withCount(['students'])->find($coordinator_id);

        // Check if coordinator does exist
        if(!$coordinator) {

            abort(404, 'Coordinator not found.');

        }

        // Return Coordinator
        return $coordinator;
    }

    /**
     * Summary of findCoordinatorIdById: A function that finds and get the ID of the Coordinator by searching the ID.
     * @param string $id
     * @return mixed
     */
    public function findCoordinatorIdById(String $id) {

        // Find and get coordinator's ID
        $coordinatorID = $this->findCoordinator($id)->id;

        // Return the ID of the coordinator
        return $coordinatorID;
    }

    /**
     * Summary of transform: A private function that transforms Coordinator's attributes.
     * @param \App\Models\Coordinator $coordinator
     * @return array
     */
    private function transform(Coordinator $coordinator)
    {

        return [
            "id" => $coordinator->id,
            "name" => $this->getFullName($coordinator->user->firstName ?? "", $coordinator->user->middle_name ?? "", $coordinator->user->last_name  ?? ""),
            "email" => $coordinator->user->email,
            "phone_number" => $coordinator->user->phone_number,
            "total_students" => $coordinator->students_count,
            "program" => $coordinator->program ? $coordinator->program->name : "No Program",
            "college" => $coordinator->program->college ? $coordinator->program->college->name : "No College",
            "created_at" => $this->formatDateOnlyDate($coordinator->user->created_at),
            "updated_at" => $this->formatDateOnlyDate($coordinator->user->updated_at),
            "deleted_at" => $this->formatDateOnlyDate($coordinator->user->deleted_at),
        ];
    }

    /**
     * Summary of updateCoordinatorById: A public function that updates a Coordinator by ID.
     * @param \App\Http\Requests\CoordinatorRequest $request
     * @param string $coordinator_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCoordinatorById(CoordinatorRequest $request, String $coordinator_id)
    {

        // Get validated
        $validated = $request->validated();

        // Find Coordinator by ID
        $coordinator = $this->findCoordinator($coordinator_id);

        // Update Coordinator and User
        $coordinator->update($validated);
        $coordinator->user->update($validated);
        $coordinator->save();

        // Return resource
        return $this->jsonResponse([
            "message" => 'A coordinator is updated.',
            'data' => new CoordinatorResource(resource: $coordinator)
        ], 201);
    }

    public function getCoordinatorByProgramId(String $programID, String $coordinatorID) {

        // Find coordinator by ID that belongs to a program by ID
        $coordinator = Coordinator::where('program_id', $programID)->find($coordinatorID);

        // Return coordinator
        return $coordinator;
    }

    public function getCoordinatorIdById(String $coordinatorID) {

        // Find and get the ID of coordinator
        $coordinatorID = Coordinator::find($coordinatorID)->id;

        // Return ID
        return $coordinatorID;

    }

    /**
     * Summary of getAllListsOfCoordinators: A public function that gets all the list of coordinators
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllListsOfCoordinators() {

        // Get authenticataed user
        $user = Auth::user();

        // Declare a variable
        $listOfCoordinators = null;

        // ! FOR ADMIN
        if($user->hasRole('admin')) {
            // Get All User (Coordinator) Info
            $listOfCoordinators = Coordinator::with(['user'])->get();
        }

        // ! FOR DEAN
        else if($user->hasRole('dean')) {

            // Get All User (Coordinator) Info
            $listOfCoordinators = Coordinator::with(['user'])->whereHas('program.college', function ($query) use ($user) {
                $query->where('dean_id', $user->id);
            })->get();

        }
        
        // ! FOR CHAIRPERSON
        else if($user->hasRole('chairperson')) {

            // Get All User (Coordinator) Info
            $listOfCoordinators = Coordinator::with(['user'])->whereHas('program', function ($query) use ($user) {
                $query->where('chairperson_id', $user->id);
            })->get();

        }
       
        // Transform list of coordinators
        $transformedCoordinators = $listOfCoordinators->map(function ($coordinator) {
            return [
                "id" => $coordinator->id,
                "name" => $this->getFullName(
                    firstName: $coordinator->user->first_name ?? "",
                    middleName: $coordinator->user->middle_name ?? "",
                    lastName: $coordinator->user->last_name ?? "",
                ),
                "program_id" => $coordinator->program_id,
            ];
        });

        // Return list of coordinators
        return $this->jsonResponse($transformedCoordinators, 200);

    }

    /**
     * Summary of getAllCoordinators: A public function that gets all Coordinators.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllCoordinators(Request $request)
    {

        // Get authenticated user
        $user = Auth::user();

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

         // Get and sanitize the search term
         $searchTerm = $this->sanitizeAndGet($request);

        // Declare variable query
        $query = null;

        // ! For Admin
        if($user->hasRole('admin')) {
            // Get All User (Coordinator) Info
            $query = Coordinator::with(['user', 'program.college'])->withCount(['students']);
        }

        // ! For Dean
        else if($user->hasRole('dean')) {

            // Check if Dean has college
            $college = College::where('dean_id', $user->id)->first();
            if(!$college) {
                return $this->jsonResponse(['message' => 'College not found.'], 403);
            }

            // Get all coordinators that belong to a specific college
            $query = Coordinator::with(['user', 'program.college'])->withCount(['students'])->whereHas('program', function ($query) use ($college) {
                $query->where('college_id', $college->id);
            });

        }

        // ! For Chairperson
        else {
            
            // Check if Chairperson has program
            $program = Program::where('chairperson_id', $user->id)->first();
            if(!$program) {
                return $this->jsonResponse(['message' => 'Program not found.'], 403);
            }

             // Get all coordinators that belong to a specific program
             $query = Coordinator::with(['user', 'program'])->withCount(['students'])->where('program_id', $program->id);

        }

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Sort by the most recent (created_at in descending order)
        $query->orderBy('created_at', 'desc');

        // Paginate the results
        $coordinators = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $coordinatorsResources = CoordinatorResource::collection($coordinators);


        // Return Coordinator Resources
        return $coordinatorsResources;
    }

    public function getAllCoordinatorsByProgramId(String $program_id)
    {

        // Get all Coordinators by program_id
        $coordinators = Coordinator::where('program_id', $program_id)->with(['user', 'program.college'])->withCount(['students'])->get();

        // Check if Coordinators exist
        if (!$coordinators) {
            return response()->json(['message' => 'Coordinators not found.'], 404);
        }

        // Transform Coordinator's Attribute
        $transformedCoordinators = $coordinators->map(function ($coordinator) {
            return $this->transform($coordinator);
        });

        // Store the login success log in the database
        Log::create([
            'user_id' => $this->user->id,
            'action_type' => 'View',
            'entity' => 'Coordinator',
            'entity_id' => $this->user->id,
            'description' => "User {$this->user->id} views coordinators successfully.",
            'status' => 'Success',
            'http_code' => 200,
            'ip_address' => request()->ip(),
        ]);

        // Return response with status 200
        return response()->json($transformedCoordinators, 200);
    }
}
