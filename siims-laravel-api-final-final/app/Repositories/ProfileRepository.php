<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProfileRepository implements ProfileRepositoryInterface
{

    /**
     * Summary of authUser
     * @var User|null
     */
    private $authUser;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->authUser = Auth::user();
    }

    /**
     * Summary of queryGet: A public function that get query of user.
     * @param array $filters
     * @return User
     */
    public function queryGet(array $filters)
    {

        // Initialize query
        $query = User::where('id', $this->authUser->id);

        // Student
        if ($this->authUser->hasRole('student') && $filters['requestedBy'] === 'student') {
            $query->with(['student.program.college', 'student.coordinator.user', 'student.workExperiences', 'student.educations', 'student.certificates']);
        }

        // Return query
        return $query;

    }


    /**
     * Summary of get: A public function that gets the profile of user.
     * @param array $filters
     * @return User
     */
    public function get(array $filters)
    {
        // Return
        return $this->queryGet($filters)->first();
    }
}
