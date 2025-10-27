<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Log;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct() {
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    /**
     * Summary of addNewRole: A public function that creates a new role record.
     * @param \App\Http\Requests\RoleRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewRole(RoleRequest $request) {

        // Get validated request
        $validated = $request->validated();

        // Mass assignment Role
        $role = Role::create($validated);

        // Store the login success log in the database
        Log::create([
            'user_id' => $this->user->id,
            'action_type' => 'Create a new Role',
            'entity' => 'Role',
            'entity_id' => $role->id,
            'description' => "User {$this->user->id} created a new role successfully.",
            'status' => 'Success',
            'http_code' => 201,
            'ip_address' => request()->ip(),
        ]);

        // Return response with status 201
        return $this->jsonResponse(['message' => 'A new role is created.', 'data' => new RoleResource($role)], 201);
      
    }

    /**
     * Summary of getAllRoles: A public function that gets all Roles.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllRoles(Request $request) {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 10);
        
        // Get all Roles
        $roles = Role::paginate($perPage);

        // Store a new log the database
        Log::create([
            'user_id' => $this->user->id,
            'action_type' => 'Views a list roles',
            'entity' => 'Roles',
            'entity_id' => "N/A",
            'description' => "User {$this->user->id} views a list of roles.",
            'status' => 'Success',
            'http_code' => 200,
            'ip_address' => request()->ip(),
        ]);

        // Return Roles
        return RoleResource::collection($roles);
        
    }
}
