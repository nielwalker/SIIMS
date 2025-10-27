<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentV2Controller extends Controller
{

    /**
     * Summary of fetchAllStudents: A private function that fetch all students in a query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function fetchAllStudents(String $requestedBy = "", String $status="")
    {

        // Get authenticated user
        $user = Auth::user();

        // Base query
        $query = Student::query();

        // Handle archived status (fetch only trashed)
        if ($status === 'archived') {
            $query = $query->onlyTrashed();
        }

        $roleQueries = [
            'admin' => function ($query) {
                return $query->with(['user', 'program.college', 'status', 'studentStatus', 'coordinator.user'])
                    ->withCount(['applications', 'endorsementLetterRequests']);
            },
            'coordinator' => function ($query) use ($user) {
                return $query->with(['user', 'program', 'studentStatus', 'latestApplication'])
                    ->where('coordinator_id', $user->id);
            },
            'dean' => function ($query) use ($user) {
                return $query->with(['user', 'program', 'status', 'coordinator.user'])
                    ->withCount(['applications', 'endorsementLetterRequests'])
                    ->whereHas('program.college', function ($query) use ($user) {
                        $query->where('dean_id', $user->id);
                    });
            },
            'chairperson' => function ($query) use ($user) {
                return $query->with(['user', 'status', 'coordinator.user', 'studentStatus'])
                    ->withCount(['applications', 'endorsementLetterRequests'])
                    ->whereHas('program', function ($query) use ($user) {
                        $query->where('chairperson_id', $user->id);
                    });
            },
            'company' => function ($query) {
                return $query->with(['user', 'program', 'status', 'coordinator.user']);
            },
            'supervisor' => function ($query) {
                return $query->with(['user', 'program', 'status', 'coordinator.user']);
            },
        ];

        // Determine role
        foreach ($roleQueries as $role => $callback) {
            if ($user->hasRole($role) && $requestedBy === $role) {
                $query = $callback($query);
                break;
            }
        }

        // Return query
        return $query;
    }

    public function getAllStudents(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Define the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Define the status
        $status = (string) $request->input('all');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all students
        $query = $this->fetchAllStudents($requestedBy, $status);

    }
}
