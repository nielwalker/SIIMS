<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupervisorRequest;
use App\Http\Resources\SupervisorResource;
use App\Http\Resources\SupervisorV2Resource;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorV2Controller extends UserController
{
    // Log Controller
    private $logController;
    // Office Controller
    private $officeController;
    // User Role Controller
    private $userRoleController;

    public function __construct(LogController $logController, OfficeV2Controller $officeV2Controller, UserRoleController $userRoleController)
    {

        $this->logController = $logController;
        $this->officeController = $officeV2Controller;
        $this->userRoleController = $userRoleController;
    }

    /**
     * Summary of restoreSupervisorByID: A public function that restores the deleted supervisor by ID.
     * @param string $supervisor_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreSupervisorByID(String $supervisor_id)
    {

        // Find Supervisor by ID
        $supervisor = Supervisor::where('user_id', $supervisor_id)->first();

        // Restore the supervisor
        $supervisor = $this->findAndRestoreModel($supervisor_id, Supervisor::class);

        // Return 
        return $this->jsonResponse([
            'message' => 'Supervisor Restored',
            'type' => 'restore',
        ], 201);
    }

    /**
     * Summary of getAllSupervisors: A public function that gets all list of supervisors.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllSupervisors(Request $request)
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Define the status
        $status = (string) $request->input('status');

        // Define the requested role by role
        $requestedBy = (string) $request->input('requestedBy');

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        /**
         * 
         * Get all supervisors
         * 
         * Conditions:
         *
         * - Get only soft deleted supervisors if the status (request) is archived
         * - Get all supervisors except for those soft deleted supervisors.
         * 
         * 
         */
        $query = $status === 'archived' ?  Supervisor::onlyTrashed()->with('user') : Supervisor::with('user');


        /**
         * Check Roles
         */

        // Admin
        if ($authUser->hasRole('admin') && $requestedBy === 'admin') {
            $query->with(['office', 'company']);
        }

        // Company
        else {

            $query->where('company_id', $authUser->id);
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

        // Paginate the results
        $supervisors = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $supervisorsResources = SupervisorV2Resource::collection($supervisors);

        // Add new log
        $this->logController->addNewLog(
            'Views the list of supervisors',
            'Supervisor',
            'N/A',
            'attempts to view the list of supervisors.',
            '200',
        );

        // Return resources
        return $supervisorsResources;
    }

    /**
     * Summary of findSupervisorByID: A public function that finds the supervisor by ID.
     * @param string $supervisorID
     * @return TModel|null
     */
    public function findSupervisorByID(String $supervisorID)
    {

        $supervisor = Supervisor::find($supervisorID);

        // Return supervisor
        return $supervisor;
    }


    /**
     * Summary of addNewSupervisor: A public function that adds a new record of supervisor.
     * @param \App\Http\Requests\SupervisorRequest $supervisorRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewSupervisor(SupervisorRequest $supervisorRequest)
    {


        // Get validated
        $validated = $supervisorRequest->validated();

        // Create a new user
        $user = $this->addNewUser($validated);

        // Find office
        $office = $this->officeController->findOfficeByID($supervisorRequest['office_id']);

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

        // Add new log
        $this->logController->addNewLog(
            'Created a new supervisor',
            'Supervisor',
            $supervisor->id,
            'adds a new supervisor.',
            '201',
        );

        // Return
        return $this->jsonResponse([
            'message' => 'A new supervisor is created.',
            'data' => new SupervisorResource($this->findSupervisorByID($supervisor->id))
        ], 201);
    }
}
