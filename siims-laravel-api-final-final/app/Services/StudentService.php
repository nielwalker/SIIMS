<?php

namespace App\Services;

use App\Repositories\StudentRepositoryInterface;

class StudentService
{

    // Auth
    private $auth;

    // Repository
    private $studentRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(StudentRepositoryInterface $studentRepositoryInterface)
    {
        $this->studentRepositoryInterface = $studentRepositoryInterface;
    }

  

    /**
     * Summary of get: Get query of students.
     * @param array $filters
     * @return mixed
     */
    public function get(array $filters) {

        // Get Query
        $query  = $this->studentRepositoryInterface->get($filters);

        // Check if section does exist
        if($filters['section']) {
            $query->where('section_id', $filters['section']);
        }

        // Paginate the results
        $students = $query->paginate($filters['perPage']);

        // Return students
        return $students;

    }

}
