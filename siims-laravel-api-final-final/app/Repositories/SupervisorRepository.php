<?php

namespace App\Repositories;

use App\Models\Supervisor;

class SupervisorRepository implements SupervisorRepositoryInterface
{

    // Model variable
    private $supervisor;

    /**
     * Create a new class instance.
     */
    public function __construct(Supervisor $supervisor)
    {
        $this->supervisor = $supervisor;
    }

    /**
     * Summary of getTotalSupervisors: A public function that gets the total of supervisors.
     * @return int
     */
    public function getTotalSupervisors() {
        return $this->supervisor->count();
    }
}
