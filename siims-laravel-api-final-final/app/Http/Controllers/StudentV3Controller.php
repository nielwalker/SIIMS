<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Services\V3\StudentV3Service;
use Illuminate\Http\Request;

class StudentV3Controller extends Controller
{

    // Services
    private $studentV3Service;
    
    public function __construct(StudentV3Service $studentV3Service) {
        $this->studentV3Service = $studentV3Service;
    }

    /**
     * Summary of get: Get students
     * @param \Illuminate\Http\Request $request
     */
    public function get(Request $request) {

        // Add filters
        $filters = [
            'perPage' => (int) $request->input('perPage', 5),
            'requestedBy' => (string) $request->input('requestedBy'),
            'searchTerm' => $this->sanitizeAndGet($request),
            'sectionID' => (string) $request->input('sectionID')
        ];

        // Get
        $students = $this->studentV3Service->get($filters);

        // Transform the paginated data into a resource collection
        $studentsResources = StudentResource::collection($students);

        // Return
        return $studentsResources;

    }


}
