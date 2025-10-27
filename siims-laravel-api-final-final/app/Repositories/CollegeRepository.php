<?php

namespace App\Repositories;

use App\Models\College;

class CollegeRepository implements CollegeRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Summary of getTotalColleges: A public function that gets the total colleges.
     * @return int
     */
    public function getTotalColleges() {
        
        // Return total colleges
        return College::count();

    }
}
