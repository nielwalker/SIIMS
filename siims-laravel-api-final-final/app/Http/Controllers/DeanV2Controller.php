<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeanRequest;
use App\Http\Resources\DeanResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DeanV2Controller extends UserController
{

    // Log Controller
    private $logController;
    // User Role Controller
    private $userRoleController;
    // College Controller
    private $collegeController;

    public function __construct(LogController $logController, UserRoleController $userRoleController, CollegeController $collegeController)
    {
        $this->userRoleController = $userRoleController;
        $this->logController = $logController;
        $this->collegeController = $collegeController;
    }

    /**
     * Summary of findDean: A private function that finds a Dean User by ID
     * @param string $dean_id
     * @return User|\Illuminate\Database\Eloquent\Collection|null
     */
    private function findDean(String $dean_id)
    {

        $dean = User::with(['college'])->whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->find($dean_id);

        // Check if dean does not exist.
        if (!$dean) {
            abort(404, 'Dean not found.');
        }

        // Return dean
        return $dean;
    }


    /**
     * Summary of updateDean: A public function that updates a Dean.
     * @param \App\Http\Requests\DeanRequest $request
     * @param string $dean_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDean(DeanRequest $request, String $dean_id)
    {

        // Get validated data
        $validated = $request->validated();

        // Find User (Dean)
        $user = $this->findDean($dean_id);

        /**
         * Check and update dean_id in the college
         */
        if ($validated['college_id']) {
            $this->collegeController->updateCollegeDeanID($user->id, $validated['college_id']);
        }

        // Update Dean
        $user->update($validated);
        $user->save();


        // Add new log
        $this->logController->addNewLog(
            'Updated a user (dean)',
            'User (Dean)',
            $user->id,
            'updates a user (dean).',
            '200',
        );

        // Return response
        return $this->jsonResponse([
            'message' => 'A dean is updated',
            'data' => new DeanResource($user)
        ], 201);
    }

    /**
     * Summary of addNewDean: A public function that creates a new Dean User.
     * @param \App\Http\Requests\DeanRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewDean(DeanRequest $request)
    {

        // Get validated data
        $validated = $request->validated();

        // Create a new User
        $user = $this->addNewUser(validated: $validated);

        /**
         * After creating the user. Create a user roel for dean
         */
        $this->userRoleController->addUserRole($user->id, 5); // Dean

        /**
         * Check and update dean_id in the college
         */
        if ($validated['college_id']) {
            $this->collegeController->updateCollegeDeanID($user->id, $validated['college_id']);
        }

        // Find Dean
        $dean = $this->findDean($user->id);


        // Add new log
        $this->logController->addNewLog(
            'Created a user (dean)',
            'User (Dean)',
            $user->id,
            'adds a user (dean).',
            '201',
        );


        // Return Dean User Resource
        return $this->jsonResponse([
            'message' => 'A new dean is created.',
            'data' => new DeanResource($dean)
        ], 201);
    }

    /**
     * Summary of deleteDean: A public function that deletes a dean.
     * @param string $dean_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDean(String $dean_id)
    {

        // Find Dean by ID
        $user = $this->findDean($dean_id);

        // Delete Dean
        $user->delete();


        // Add new log
        $this->logController->addNewLog(
            'Deletes a dean',
            'Dean',
            'N/A',
            'attempts to delete a dean.',
            '200',
        );
        // Return response
        return $this->jsonResponse([
            'message' => 'Dean is deleted.'
        ], 201);
    }


    /**
     * Summary of getAllDeans: A public function get all deans.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllDeans(Request $request)
    {

        // Define the status
        $status = (string) $request->input('status');

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('per_page', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        /**
         * 
         * Get all users (dean)
         * 
         * Conditions:
         *
         * - Get only soft deleted users (dean) if the status (request) is archived
         * - Get all users (dean) except for those soft deleted users (deans).
         * 
         * 
         */
        $query = $status === 'archived' ? User::onlyTrashed()->with(['roles']) : User::with(['roles']);

        // Get only deans
        $query = $query->whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->with('college');

        // Set Order by "created_at (descending)"
        $query->orderBy('created_at', 'desc');

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {

            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            })->OrWhere('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $deans = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $deansResources = DeanResource::collection($deans);

        // Add new log
        $this->logController->addNewLog(
            'Views the list of deans',
            'Dean',
            'N/A',
            'attempts to view the list of deans.',
            '200',
        );

        // Return
        return $deansResources;
    }

    /**
     * Summary of restoreDeanByID: A public function that restores the Dean by ID.
     * @param string $dean_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreDeanByID(String $dean_id)
    {

        // Restore user
        $user = $this->findAndRestoreModel($dean_id, User::class);

        // Add new log
        $this->logController->addNewLog(
            'Restores a dean',
            'Dean',
            $user->id,
            'attempts to restore a dean',
            '200',
        );


        // Return 
        return $this->jsonResponse([
            'message' => 'Dean Restored',
            'type' => 'restore',
        ], 201);
    }
}
