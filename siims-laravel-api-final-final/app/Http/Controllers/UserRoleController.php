<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserRoleController extends Controller
{


    /**
     * Summary of updateOrCreate: A public function that updates or creates a new user role.
     * @param \App\Models\User $user
     * @param string $roleID
     * @return void
     */
    public function updateOrCreate(User $user, String $roleID) {

        UserRole::updateOrCreate(
            ['user_id' => $user->id, 'role_id' => $roleID],
            [
                'user_id' => $user->id,
                "role_id" => 7,
            ]
        );

    }

    /**
     * Summary of addUserRole: A public function that adds a new role for User.
     * @param string $id
     * @param string $roleID
     * @return void
     */
    public function addUserRole(String $id, String $roleID) {
        UserRole::create([
            'user_id' => $id,
            'role_id' => $roleID,

            // * Added at December 22, 2024: Set the start_date to the current date and time
            'start_date' => Carbon::now()->toDateString(), // Current date in Manila timezone
        ]);
    }

    /**
     * Summary of getAllUserRoles: A public function that gets all User Roles.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllUserRoles() {

        // Get all User Roles
        $users = User::withTrashed()->with(['roles'])->get()->map(function ($user) {
            $user->roles = $user->roles->map(function ($role) {
                // Remove pivot attribute from Roles
                return [
                    'id' => $role->id,
                    'name' => ucwords($role->name),
                ];
            });

            return $user;
        });


        // Transform Users Attributes
        $transformedUsers = $users->map(function ($user) {
            return [
                "id" => $user->id,
                "roles" => $user->roles,
                "first_name" => $user->first_name,
                "middle_name" => $user->middle_name,
                "last_name" => $user->last_name,
                "email" => $user->email,
                "email_verified_at" => $this->formatDate($user->email_verified_at),
                "gender" => $user->gender ? ucwords($user->gender) : "",
                "phone_number" => $user->phone_number,
                "street" => $user->street,
                "barangay" => $user->barangay,
                "city_municipality" => $user->city_municipality,
                "province" => $user->province,
                "postal_code" => $user->postal_code,
                "created_at" => $this->formatDateOnlyDate($user->created_at),
                "updated_at" => $this->formatDateOnlyDate($user->updated_at),
                "deleted_at" => $this->formatDateOnlyDate($user->deleted_at),
            ];
        });

        // Return response with status 200;
        return response()->json($transformedUsers, 200);

    }

    /**
     * Summary of getUserRoles: A public function that gets all User Roles
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getUserRoles()
    {

        // Get Auth User Roles
        $userRoles = Auth::user()->roles;

        // Check User roles
        if (!$userRoles) {
            return response()->json(['message' => 'You are not authorized to access this system.'], 401);
        }

        // Transform User Roles Attributes
        $transformedUserRoles = $userRoles->map(function ($userRole) {
            return $userRole->name;
        });

        // Optional: Sort the roles alphabetically
        $transformedUserRoles = $transformedUserRoles->sort()->values();

        // Return response with status 200
        return response()->json($transformedUserRoles, 200);
    }
}
