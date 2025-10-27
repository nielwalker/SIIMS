<?php

namespace App\Services;

use App\Repositories\ProfileRepositoryInterface;

class ProfileService
{

    // Repositories
    private $profileRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(ProfileRepositoryInterface $profileRepositoryInterface)
    {
        $this->profileRepositoryInterface = $profileRepositoryInterface;
    }

    /**
     * Summary of get: Get profile from user.
     * @param array $filters
     * @return mixed
     */
    public function get(array $filters) {

        // Get Profile
        $profile = $this->profileRepositoryInterface->get($filters);

        // Return 
        return $profile;

    }
}
