<?php

namespace App\Repositories;

use App\Models\Office;

class OfficeRepository implements OfficeRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    
    /**
     * Summary of getTotalOffices: A public function that gets the total offices.
     * @return int
     */
    public function getTotalOffices() {

        return Office::count();

    }
}
