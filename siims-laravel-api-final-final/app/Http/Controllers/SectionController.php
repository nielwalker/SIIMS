<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignSectionRequest;
use App\Http\Requests\SectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Repositories\SectionRepositoryInterface;
use App\Services\SectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{


    /**
     * Log Controller
     */
    private $logController;

    // Repository
    private $sectionRepositoryInterface;

    // Service
    private $sectionService;

    // DocumentTypeV2Controller constructor
    public function __construct(SectionRepositoryInterface $sectionRepositoryInterface, SectionService $sectionService)
    {

        $this->sectionRepositoryInterface = $sectionRepositoryInterface;
        $this->sectionService = $sectionService;
    }

    /**
     * Summary of assignStudents: Assigns the student in the given section
     * @param \App\Http\Requests\AssignSectionRequest $assignSectionRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignStudents(AssignSectionRequest $assignSectionRequest, String $section_id) {

        // Get validated
        $validated = $assignSectionRequest->validated();

        // Call Service
        $this->sectionService->assign($validated, $section_id);

        // Return
        return $this->jsonResponse(['message' => 'Student assigned'], 200);

    }

    /**
     * Summary of create: A public function that adds a new section record.
     * @param \App\Http\Requests\SectionRequest $sectionRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(SectionRequest $sectionRequest)
    {

        // Get validated data
        $validated = $sectionRequest->validated();

        // Add filters
        $filters = [
            'requestedBy' => $sectionRequest->query('requestedBy'),
        ];

        // Create new section
        $section = $this->sectionService->create($filters, $validated, $sectionRequest);

        // Return
        return $this->jsonResponse([
            'message' => "A new section created.",
            'data' => new SectionResource($section),
        ], 201);


        // Import Students
        /* $notFound = $this->sectionRepositoryInterface->importAndAddToSection($section->id, sectionRequest: $sectionRequest); */

        // Initialize message variable
        /* $message = empty($notFound) ? "A new section is created. All students imported successfully." : "A new section is created. Students not found: {$notFound}"; */

        // Return
        /* return $this->jsonResponse([
            'message' => $message,
            'data' => new SectionResource($section),
        ], 201); */
    }

    /**
     * Summary of get: A public function that gets a maximum of 10 sections
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Add filters
        $filters = [
            'requestedBy' => $request->query('requestedBy'),
            'searchTerm' => $searchTerm,
        ];

        // Get Sections
        $sections = $this->sectionService->get($filters);

        // Transform the data into a resource collection
        $sectionResources = SectionResource::collection($sections);

        // Return
        return $this->jsonResponse($sectionResources);
    }
}
