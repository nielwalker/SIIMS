<?php

namespace App\Http\Controllers;

use App\Http\Requests\OsaRequest;
use App\Http\Resources\OsaResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class OsaController extends UserController
{
    /**
     * The user role controller
     */
    private $userRoleController;
    
    /**
     * OsaController constructor.
     */
    public function __construct(UserRoleController $userRoleController) {
        $this->userRoleController = $userRoleController;
    }

    /**
     * Summary of deleteOsaUserById: A public function that deletes the OSA user by ID.
     * @param string $osa_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOsaUserById(String $osa_id) {

        // Find Osa User by ID
        $osa = User::find($osa_id);

        // Delete OSA
        $osa->delete();

        // Return message and status
        return $this->jsonResponse([
            'message' => 'An OSA user is deleted',
            
        ], 201);

    }

    /**
     * Summary of restoreOsaUserById: A public function that restore the deleted OSA user by ID
     * @param string $osa_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreOsaUserById(String $osa_id) {

        // Find the deleted OSA user and restore
        $osa = User::withTrashed()->where('id', $osa_id)->restore();

        // Return the data, status, and message
        return $this->jsonResponse([
            'message' => 'An OSA user is restored.',
            'data' => new OsaResource(User::find($osa_id)),
        ], 201);

    }

    /**
     * Summary of updateOsaUserById: A public function that updates an OSA user by ID.
     * @param \App\Http\Requests\OsaRequest $osaRequest
     * @param string $osa_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOsaUserById(OsaRequest $osaRequest, String $osa_id) {

        // Get validated
        $validated = $osaRequest->validated();

        // Find OSA User by ID.
        $osa = User::find($osa_id);

        // Update and save OSA
        $osa->update($validated);
        $osa->save();

        // Return message, status, and data
        return $this->jsonResponse([
            'message' => 'An OSA user is updated.',
            'data' => new OsaResource($osa)
        ], 201);

    }

    /**
     * Summary of addNewOsa: A public function that adds new OSA user.
     * @param \App\Http\Requests\OsaRequest $osaRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewOsa(OsaRequest $osaRequest) {

        // Get validated
        $validated = $osaRequest->validated();

        // Create new user
        $user = $this->addNewUser($validated);

        // Create a user role
        $this->userRoleController->addUserRole($user->id, 6); // OSA role ID

        // Return status, message, and data
        return $this->jsonResponse([
            'message' => 'A new OSA user is created',
            "data" => new OsaResource($user)
        ], 201);

    }
    
    /**
     * Summary of getAllOsaUsers: A public function that gets all Users that has a role of OSA.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllOsaUsers(Request $request) {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Get All User (OSA) Info
        $query = $this->fetchAllUsers();

        // Get only users that has a role of OSA
        $query = $query->whereHas('roles', function ($query) {
            $query->where('name', 'osa');
        });

        // Apply the search filter if search term is provided
        if(empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $osaUsers = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $osaUsersResources = OsaResource::collection($osaUsers);

        // Return OSA Users
        return $osaUsersResources;
    }
}
