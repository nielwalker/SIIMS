<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupervisorRequest;
use App\Http\Resources\SupervisorResource;
use App\Models\Office;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorController extends UserController
{
     /**
     * The user role controller
     */
    private $userRoleController;
     /**
     * The office controller
     */
    private $officeController;
    /**
     * SupervisorController constructor
     */
    public function __construct(UserRoleController $userRoleController, OfficeController $officeController, ) {
        $this->userRoleController = $userRoleController;
        $this->officeController = $officeController;
    }

    /**
     * Summary of fetchAllSupervisors: A private function that gets all supervisors.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function fetchAllSupervisors() {

        // Get authenticated user
        $authUser = Auth::user();

        // Declare empty variable
        $query = Supervisor::with(['user']);

        // ! FOR ADMIN
        if ($authUser->hasRole('admin')) {

            // Get Supervisor (Admin)
            // $query = Supervisor::with(['user', 'office', 'company']);
            // Admin: Fetch all supervisors with detailed relationships
        $query->with(['office', 'company']);
        }
    
        // ! FOR COMPANY
        else {
            // Get supervisors that belongs to a company
            // $query = Supervisor::with(['user'])->where('company_id', $authUser->id);
            // Non-admin (e.g., company): Fetch supervisors belonging to the user's company
        $query->where('company_id', $authUser->id);
        }
        //dd($query->get());
        // Return query
        return $query;
    }

    /**
     * Summary of findSupervisorById: A private function that finds a supervisor by ID
     * @param string $supervisorID
     * @return TModel|null
     */
    private function findSupervisorById(String $supervisorID) {

        // Get and find supervisor by ID
        $supervisor = $this->fetchAllSupervisors()->find($supervisorID);

        // Return supervisor
        return $supervisor;

    }

    /**
     * Summary of deleteSupervisorById: A public function that deletes a supervisor by ID
     * @param string $supervisor_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSupervisorById(String $supervisor_id) {

        // Find Supervisor By ID
        $supervisor = $this->findSupervisorById($supervisor_id);

        // Delete supervisor
        $supervisor->delete();

        // Return status and message
        return $this->jsonResponse([
            'message' => 'Supervisor is deleted'
        ], 201);

    }

    /**
     * Summary of updateSupervisorById: A public function that updates a supervisor by ID.
     * @param \App\Http\Requests\SupervisorRequest $supervisorRequest
     * @param string $supervisor_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSupervisorById(SupervisorRequest $supervisorRequest, String $supervisor_id) {
        
        // Get authenticated user
        $authUser = Auth::user();

        // Get validated
        $validated = $supervisorRequest->validated();

        // Find Supervisor by ID
        $supervisor = $this->findSupervisorById($supervisor_id);

        /**
         * Update Supervisor and User
         */
        
        // ! FOR ADMIN
        if($authUser->hasRole('admin')) {

            // Find office
            $office = $this->officeController->findOffice($validated['office_id']);
            // Update the office
            $supervisor->company_id = $office->company_id;
           
            // Update Office Assign
            $this->officeController->assignSupervisorById($supervisor->id, $office->id);
        }

        // ! FOR COMPANY
        else if($authUser->hasRole('company')) {

            // Find office that belongs to a company
            $office = Office::where('company_id', $authUser->id)->find($validated['office_id']);

            // Update Office Assign
            $this->officeController->assignSupervisorById($supervisor->id, $office->id);

            // Update and save
            $supervisor->update($validated);
            $supervisor->user->update($validated);

        }

        else {
           abort(400, 'Something is wrong...');
        }

        // Update user 
        $supervisor->user->update($validated);

        // Save changes
        $supervisor->save();

        // Return 
        return $this->jsonResponse([
            'message' => "A supervisor is updated",
            "data" => new SupervisorResource($this->findSupervisorById($supervisor->id))
        ], 201);
     
    }

    /**
     * Summary of addNewSupervisor: A public function that adds a new supervisor.
     * @param \App\Http\Requests\SupervisorRequest $supervisorRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewSupervisor(SupervisorRequest $supervisorRequest) {    

        // Get validated
        $validated = $supervisorRequest->validated();

        // Create a new user
        $user = $this->addNewUser($validated);

        // Find office
        $office = $this->officeController->findOffice($validated['office_id']);

        // Create Supervisor
        $supervisor = Supervisor::create([
            "id" => $user->id,
            "user_id" => $user->id,
            "company_id" => $office->company_id,
        ]);

        // Create User Role
        $this->userRoleController->addUserRole($user->id, 8); // Supervisor role ID

        // Assign the supervisor in the office
        $this->officeController->assignSupervisorById($supervisor->id, $office->id);

        // Return data, message, and status
        return $this->jsonResponse([
            'message' => 'A new supervisor is created.',
            'data' => new SupervisorResource($this->findSupervisorById($supervisor->id))
        ], 201);
    }

    /**
     * Summary of getAllSupervisors: A public function that gets all supervisors.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllSupervisors(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        /**
         * Fetch all supervisors
         * 
         * Admin    - Fetch all supervisors
         * Company  - Fetch all supervisors that belongs to a company. 
         */
        $query = $this->fetchAllSupervisors();

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $supervisors = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $supervisorsResources = SupervisorResource::collection($supervisors);

        // Return Supervisor Resources
        return $supervisorsResources;
    }
}
