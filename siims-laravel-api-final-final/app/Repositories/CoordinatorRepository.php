<?php

namespace App\Repositories;

use App\Models\Coordinator;

class CoordinatorRepository implements CoordinatorRepositoryInterface
{

    // Model variable
    private $coordinator;

    /**
     * Create a new class instance.
     */
    public function __construct(Coordinator $coordinator)
    {
        $this->coordinator = $coordinator;
    }

    /**
     * Summary of getTotalCoordinators: A public function that gets the total of coordinators.
     * @return int
     */
    public function getTotalCoordinators() {
        return $this->coordinator->count();
    }
}
