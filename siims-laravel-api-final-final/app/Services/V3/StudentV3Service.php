<?php

namespace App\Services\V3;

use App\Http\Resources\StudentResource;
use App\Models\User;
use App\Repositories\V3\StudentV3RepositoryInterface;
use Illuminate\Support\Facades\Auth;

class StudentV3Service
{

    // Repositories
    private $studentV3RepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(StudentV3RepositoryInterface $studentV3RepositoryInterface)
    {

        $this->studentV3RepositoryInterface = $studentV3RepositoryInterface;
    }

    /**
     * Summary of get: Get query of students.
     * @param array $filters
     * @return mixed
     */
    public function get(array $filters)
    {

        // Get Query
        $query  = $this->studentV3RepositoryInterface->get($filters);

        // Check if section does exist
        if (isset($filters['sectionID']) && $filters['sectionID'] === 'no-sections') {
        
            $query->whereNull('section_id'); // Get students with no section
        } else if ($filters['sectionID']) {
            $query->where('section_id', $filters['sectionID']);
        }

        $query->with(['dailyEntries']);

        // Paginate the results
        $students = $query->paginate($filters['perPage']);

        // Return students
        return $students;
    }
}
