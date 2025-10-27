<?php

namespace App\Repositories\V3;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentV3Repository implements StudentV3RepositoryInterface
{

    /**
     * Summary of authUser
     * @var User
     */
    private $authUser;


    // Model
    private $student;

    /**
     * Create a new class instance.
     */
    public function __construct(Student $student)
    {
        $this->student = $student;
        $this->authUser = Auth::user();
    }


    /**
     * Apply search filter to the query.
     *
     * @param Builder $query
     * @param string $searchTerm
     */
    private function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->whereHas('user', function ($q) use ($searchTerm) {
            $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
        });
    }

    /**
     * Apply sorting to the query.
     *
     * @param Builder $query
     */
    private function applySorting(Builder $query): void
    {
        $query->with('user')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->orderBy('users.first_name')
            ->orderBy('users.last_name');
    }

    /**
     * Apply role-based filters to the query.
     *
     * @param Builder $query
     * @param array $filters
     */
    private function applyRoleBasedFilters(Builder $query, array $filters): void
    {
        if (!isset($filters['requestedBy'])) return;

        $requestedBy = $filters['requestedBy'];
        $user = $this->authUser;

        // Define queries based on role
        $roleQueries = [
            'admin' => function ($query) {
                $query->with(['user', 'program.college', 'status', 'studentStatus', 'coordinator.user'])
                    ->withCount(['applications', 'endorsementLetterRequests']);
            },
            'coordinator' => function ($query) use ($user) {
                $query->with([
                    'user',
                    'program.college',
                    'studentStatus',
                    'latestApplication',
                    'applications.applicationStatus',
                    'applications.workPost.office.company',
                    'certificates'
                ])
                    ->where('coordinator_id', '=', $user->coordinator->user_id)
                    ->withCount(['applications']);
            },
            'dean' => function ($query) use ($user) {
                $query->with(['user', 'program', 'status', 'coordinator.user'])
                    ->withCount(['applications', 'endorsementLetterRequests'])
                    ->whereHas('program.college', fn($q) => $q->where('dean_id', $user->id));
            },
            'chairperson' => function ($query) use ($user) {
                $query->with(['user', 'status', 'coordinator.user', 'studentStatus'])
                    ->withCount(['applications', 'endorsementLetterRequests'])
                    ->whereHas('program', fn($q) => $q->where('chairperson_id', $user->id));
            },
            'company' => function ($query) {
                $query->with(['user', 'program', 'status', 'coordinator.user']);
            },
        ];

        // Apply query modifications based on role
        if ($user->hasRole($requestedBy) && isset($roleQueries[$requestedBy])) {
            $roleQueries[$requestedBy]($query);
        }
    }



    /**
     * Get students query with filters.
     * 
     * @param array $filters
     * @return Builder
     */
    public function get(array $filters = []): Builder
    {
        // Initialize base query
        $query = $this->student->query();

        // Apply role-based conditions
        $this->applyRoleBasedFilters($query, $filters);

        // Apply search filter if provided
        if (!empty($filters['searchTerm'])) {
            $this->applySearchFilter($query, $filters['searchTerm']);
        }

        // Apply sorting
        $this->applySorting($query);

        return $query;
    }
}
