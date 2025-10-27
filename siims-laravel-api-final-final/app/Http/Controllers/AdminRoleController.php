<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActionLogger;

class AdminRoleController extends Controller
{
    // Reusable method for logging actions
    private function logAction(ActionLogger $actionLogger, $actionType, $entity, $entityId, $description, $status, $httpCode)
    {
        $actionLogger->logAction(
            actionType: $actionType,
            entity: $entity,
            entityId: $entityId,
            description: $description,
            status: $status,
            httpCode: $httpCode,
        );
    }

    // Reusable method for handling "not found" scenarios
    private function handleNotFound(ActionLogger $actionLogger, $entity, $message)
    {
        $this->logAction(
            actionLogger: $actionLogger,
            actionType: 'View',
            entity: $entity,
            entityId: 0,
            description: "$entity not found during fetching.",
            status: 'Failed',
            httpCode: 404,
        );

        return response()->json(['message' => $message], 404);
    }

    // A public function that gets all roles
    public function getAllRoles(ActionLogger $actionLogger)
    {
        $roles = Role::all();

        if ($roles->isEmpty()) {
            return $this->handleNotFound($actionLogger, 'Role', 'Roles not found');
        }

        // Transform roles to include human-readable created_at and exclude updated_at
        $transformedRoles = $roles->map(function ($role) {
            return $this->transformRole($role);
        });

        $this->logAction(
            actionLogger: $actionLogger,
            actionType: 'View',
            entity: 'Role',
            entityId: 0,
            description: "Roles are found and fetched.",
            status: 'Success',
            httpCode: 200,
        );

        // Get Roles
        // Get User Roles
        return response()->json([
            "initialRoles" => $transformedRoles,
            "userRoles" => $this->getUserRoles(actionLogger: $actionLogger)->original,
        ]);
    }

    // A public function that adds a new role record
    public function addNewRole(AdminRoleRequest $request, ActionLogger $actionLogger)
    {
        $validatedCredentials = $request->validated();
        $validatedCredentials['name'] = strtolower($validatedCredentials['name']);

        $role = Role::create($validatedCredentials);

        $status = $role ? 'Success' : 'Failed';
        $httpCode = $role ? 201 : 400;
        $responseMessage = $role ? 'A new role has been created.' : 'The role was not created.';
        $description = $role ? 'New role created successfully' : 'Failed to create role';

        $this->logAction(
            actionLogger: $actionLogger,
            actionType: 'Create',
            entity: 'Role',
            entityId: $role ? $role->id : null,
            description: $description,
            status: $status,
            httpCode: $httpCode,
        );

        // Transform Role
        $transformRole = $this->transformRole($role);

        return response()->json(['message' => $responseMessage, 'data' => $transformRole], $httpCode);
    }

    // Transform Roles
    private function transformRole($role)
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'created_at' => \Carbon\Carbon::createFromTimestamp($role->created_at)->format('F j, Y h:i A'), // Convert timestamp to human-readable format
        ];
    }

    /**
     * A private function that gets all user roles
     * @param \App\Services\ActionLogger $actionLogger
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function getUserRoles(ActionLogger $actionLogger)
    {
        $users = User::has('roles')->with('roles')->get();

        if ($users->isEmpty()) {
            return $this->handleNotFound($actionLogger, 'User Roles', 'Users not found.');
        }

        $transformedUsers = $users->map(function ($user) {
            return [
                'roles' => [
                    'name' => $user->roles->pluck('name'),
                ],
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'middle_name' => $user['middle_name'],
                'last_name' => $user['last_name'],
            ];
        });

        $this->logAction(
            actionLogger: $actionLogger,
            actionType: 'View',
            entity: 'User Roles',
            entityId: 0,
            description: "User roles found during fetching.",
            status: 'Success',
            httpCode: 200,
        );

        return response()->json($transformedUsers, 200);
    }
}
