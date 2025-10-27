<?php

namespace App\Services\V3;

use App\Http\Resources\CollegeResource;
use App\Repositories\V3\CollegeV3RepositoryInterface;

class CollegeV3Service
{

    // Repositories
    private $collegeV3RepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(CollegeV3RepositoryInterface $collegeV3RepositoryInterface)
    {
        $this->collegeV3RepositoryInterface = $collegeV3RepositoryInterface;
    }

    /**
     * Summary of get: Get all colleges
     * @param array $filters
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function get(array $filters = [])
    {

        // Initialize
        $college = null;

        // Check Status if (archived)
        if ($filters['status'] === 'archived') {
            $college = $this->collegeV3RepositoryInterface->getArchive();
        } else {

            $college = $this->collegeV3RepositoryInterface->get();
        }

        // With
        $college->with(['dean']);

        // Check Search
        if (!empty($filters['searchTerm'])) {
            $college->where('name', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%');
        }

        // Paginate
        $colleges = $college->paginate($filters['perPage']);

        // Transform and return
        return CollegeResource::collection($colleges);
    }
}
