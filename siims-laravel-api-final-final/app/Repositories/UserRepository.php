<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository implements UserRepositoryInterface
{

    // Model variable
    private $user;

    /**
     * Create a new class instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Summary of getTotalUsers: A public function that gets total of users.
     * @return int
     */
    public function getTotalUsers()
    {

        // Return total users
        return $this->user->count();
    }
}
