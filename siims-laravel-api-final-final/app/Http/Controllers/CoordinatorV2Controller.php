<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoordinatorRequest;
use App\Http\Resources\CoordinatorResource;
use App\Http\Resources\SearchCoordinatorResource;
use App\Http\Resources\SearchEndorseStudentResource;
use App\Models\College;
use App\Models\Coordinator;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoordinatorV2Controller extends UserController
{

    // Log Controller
    private $logController;

    // UserRole Controller
    private $userRoleController;


    public function __construct(LogController $logController, UserRoleController $userRoleController)
    {
        $this->userRoleController = $userRoleController;
        $this->logController = $logController;
    }

     /**
     * Summary of queryCoordinators: A private function that queries a coordinator.
     * @param string $status
     * @param string $requestedBy
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Http\JsonResponse
     */
    private function queryCoordinators(String $status = "", String $requestedBy = "",) {

        // Get authenticated user
        $authUser = Auth::user();
        
        $query = $status === 'archived' ? Coordinator::onlyTrashed()->with(['user', 'program']) :
        Coordinator::with(['user', 'program']);

        // Check if role is Dean
        if ($authUser->hasRole('dean') && $requestedBy === 'dean') {

            // Find college where the dean ID exist
            $college = College::where('dean_id', $authUser->id)->first();

            // Check if Dean has college
            if (!$college) {
                return $this->jsonResponse(['message' => 'College not found.'], 403);
            }

            $query = $query->whereHas('program', function ($query) use ($college) {
                $query->where('college_id', $college->id);
            });
        } else if ($authUser->hasRole('chairperson') && ($requestedBy === 'chairperson' || $requestedBy === 'admin')) {

            // Treat chairperson like admin for listing: see all coordinators
            // If you prefer to restrict to the chairperson's program, replace the next line with the program filter
            $query = $query->with(['program.college']);

        } else if (($authUser->hasRole('admin') && $requestedBy === 'admin')) {
            $query = $query->with(['program.college']);
        }

       
        else {
            // Abort 400 request
            abort(400, 'Invalid request');
        }

        // Return query
        return $query->withCount(['students']);
    }

     /**
     * Summary of findAndTransform: A private function that finds and transform coordinator.
     * @param string $requestedBy
     * @param string $id
     * @return CoordinatorResource
     */
    private function findAndTransform(String $requestedBy = "", String $id){

        // Get Coordinator and transform
        $transformedCoordinator = new CoordinatorResource($this->queryCoordinators(requestedBy: $requestedBy)->find($id));

        // Return
        return $transformedCoordinator;
    }


    /**
     * Summary of restoreCoordinatorByID: A public function that restores a coordinator by ID.
     * @param string $coordinator_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreCoordinatorByID(String $coordinator_id) {

        // Find Coordinator
        $coordinator = $this->findAndRestoreModel($coordinator_id, Coordinator::class);

        // Add new log
        $this->logController->addNewLog(
            'Restores a coordinator',
            'Coordinator',
            $coordinator->id,
            'attempts to restore a coordinator',
            '200',
        );

        
         // Return 
         return $this->jsonResponse([
            'message' => 'Coordinator Restored',
            'type' => 'restore',
        ], 201);

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
      
        // Delete Coordinator and User and roles
        $coordinator->delete();

        // Return response with status 201
        return $this->jsonResponse(['message' => "Coordinator is deleted."], 201);
     
    }

     /**
     * Summary of updateCoordinatorById: A public function that updates a Coordinator by ID.
     * @param \App\Http\Requests\CoordinatorRequest $request
     * @param string $coordinator_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCoordinatorById(CoordinatorRequest $request, String $coordinator_id)
    {

        // Define the requested role by role
        $requestedBy = (string) $request->input('requestedBy');

        // Get validated
        $validated = $request->validated();

        // Find Coordinator by ID
        $coordinator = $this->queryCoordinators(requestedBy: $requestedBy)->find($coordinator_id);

        // Update Coordinator and User
        $coordinator->update($validated);
        $coordinator->user->update($validated);
        $coordinator->save();

        // Return resource
        return $this->jsonResponse([
            "message" => 'A coordinator is updated.',
            'data' => $this->findAndTransform($requestedBy, $coordinator_id)
        ], 201);
    }

    /**
     * Summary of addNewCoordinator: A public function that adds a new Coordinator
     * @param \App\Http\Requests\CoordinatorRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewCoordinator(CoordinatorRequest $request)
    {

        // Define the requested role by role
        $requestedBy = (string) $request->input('requestedBy');

        // Get validated
        $validated = $request->validated();

        // Create a new User
        $user = $this->addNewUser($validated);

        // Add New Coordinator
        if ($user) {

            // Create Coordinator
            Coordinator::create([
                'id' => $user->id,
                "user_id" => $user->id,
                "program_id" => $validated['program_id'],
            ]);

            // Create User Role
            $this->userRoleController->addUserRole($user->id, 3); // ID of role Coordinator
        }

        // Get Coordinator and transform
        $transformedCoordinator = $this->findAndTransform($requestedBy, $user->id);

        // Add new log
        $this->logController->addNewLog(
            'Created a new coordinator',
            'Coordinator',
            $user->id,
            'adds a new coordinator.',
            '201',
        );

        // Return resource
        return $this->jsonResponse([
            "message" => 'A new coordinator is created.',
            "data" => $transformedCoordinator,
        ], 201);
    }

    /**
     * Summary of getAllCoordinators: A public function that gets all Coordinators.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllCoordinators(Request $request)
    {

        // Define the status
        $status = (string) $request->input('status');
        // Define the requested role by role
        $requestedBy = (string) $request->input('requestedBy');

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        /**
         * - Query Coordinators
         * - status (all, or archived)
         * - requestedBy (role of the user requesting)
         * @var mixed
         */
        $query = $this->queryCoordinators(status: $status, requestedBy: $requestedBy);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('id', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Sort by the most recent (created_at in descending order)
        $query->orderBy('created_at', 'desc');

        // Paginate the results
        $coordinators = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $coordinatorsResources = CoordinatorResource::collection($coordinators);

        // Add new log
        $this->logController->addNewLog(
            'Views the list of coordinators',
            'Coordinator',
            'N/A',
            'attempts to view the list of coordinators.',
            '200',
        );

        // Return Coordinator Resources
        return $coordinatorsResources;
    }

    /**
     * Summary of searchCoordinator: A public function that searches the coordinator by ID, email, or name.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchCoordinator(Request $request)
    {

        $query = $request->input('query');

        $coordinators = Coordinator::whereHas('user', function ($q) use ($query) {
            $q->where('first_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('middle_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('last_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('email', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('id', 'LIKE', '%' . $query . '%'); // Missing semicolon added here
        })->get();


        // Transform to resources
        $coordinatorResources = SearchCoordinatorResource::collection($coordinators);

        // Return list of coordinators
        return $this->jsonResponse($coordinatorResources, 200);
    }
}
