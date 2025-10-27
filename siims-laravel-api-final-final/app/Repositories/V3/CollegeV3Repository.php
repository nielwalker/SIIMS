<?php

namespace App\Repositories\V3;

use App\Models\College;
use App\Repositories\CollegeRepositoryInterface;

class CollegeV3Repository implements CollegeV3RepositoryInterface
{
    // Model
    private $college;

    /**
     * Create a new class instance.
     */
    public function __construct(College $college)
    {
        $this->college = $college;
    }
    
    /**
     * Summary of get: Get 
     * @return \Illuminate\Database\Eloquent\Builder<College>
     */
    public function get()
    {
        
        // Query college
        return $this->college->query();

    }

    /**
     * Summary of getArchive: Get archive
     * @return \Illuminate\Database\Eloquent\Builder<mixed>
     */
    public function getArchive() {
        // Query college only trashed
        return $this->college->onlyTrashed();
    }
}
