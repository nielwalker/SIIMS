<?php

namespace App\Http\Controllers;

use App\Services\V3\CollegeV3Service;
use Illuminate\Http\Request;

class CollegeV3Controller extends Controller
{

    // Services
    private $collegeV3Service;

    public function __construct(CollegeV3Service $collegeV3Service) {
        $this->collegeV3Service = $collegeV3Service;
    }
    
    /**
     * Summary of get: Get all colleges
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function get(Request $request) {

        // Add Filters
        $filters = [
            'perPage' => (int) $request->query('perPage') ?? 5,
            'searchTerm' => (string) $this->sanitizeAndGet($request),
            'status' => $request->query('status'),
        ];

        // Get
        $colleges = $this->collegeV3Service->get($filters);

        // Return
        return $colleges;

    }

}
