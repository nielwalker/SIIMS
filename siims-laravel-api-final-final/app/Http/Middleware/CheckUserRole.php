<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {   

        /** @var \App\Models\User $user */
        // $user = Auth::user();

        // Check if the user is authenticated and has one of the required roles
        /* if (!$user || !$user->roles->pluck('name')->intersect($roles)->count()) {
            // If the user does not have any of the required roles, return a 403 Forbidden response
            return response()->json(['error' => 'Forbidden'], 403);
        } */

        // If the user has the required role, pass the request to the next middleware or controller
        // return $next($request);

        /**
         * * Updated CheckUserRole Middleware
         */

         /** @var \App\Models\User $user */
         $user = Auth::user();

         // Check if the user is authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // If the authenticated user is a valid chairperson, grant universal access (treat as admin)
        $chairpersonRole = $user->roles->firstWhere('name', 'chairperson');
        if ($chairpersonRole) {
            $assignment = $chairpersonRole->pivot;
            if (!$assignment || !$assignment->end_date || Carbon::parse($assignment->end_date)->gte(now())) {
                return $next($request);
            }
        }

        // Debug: Log user roles for troubleshooting
        \Log::info('User roles check', [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'required_roles' => $roles,
            'request_url' => $request->url()
        ]);

        // Check if the user has one of the required roles and that the role is not expired
        $hasValidRole = $user->roles->filter(function ($role) use ($roles) {
            // Check if the role matches the provided ones and if the role has expired
            $isRoleValid = $role->name && in_array($role->name, $roles);
            $roleAssignment = $role->pivot; // Access the pivot table where start_date and end_date are stored
            
            // Check if the end_date has passed
            if ($isRoleValid && ($roleAssignment->end_date && Carbon::parse($roleAssignment->end_date)->lt(now()))) {
                return false; // Role is expired
            }
            
            return $isRoleValid;
        })->isNotEmpty();

        // If no valid role, return 403 Forbidden
        if (!$hasValidRole) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // If the user has the required and valid role, pass the request to the next middleware or controller
        return $next($request);

    }


}
