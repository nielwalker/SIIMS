<?php

namespace App\Repositories;

use App\Models\User;

class DeanRepository implements DeanRepositoryInterface
{

    // Model variable
    private $dean;

    /**
     * Create a new class instance.
     */
    public function __construct(User $user)
    {
        $this->dean = $user::whereHas('roles', function ($query) {
            $query->where('role_id', 5);
        });
    }

    /**
     * Summary of getTotalDeans: A public function that gets the total of deans.
     * @return int
     */
    public function getTotalDeans() {

        return $this->dean->count();

    }
}
