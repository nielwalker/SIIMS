<?php

namespace App\Services;

use App\Http\Requests\SectionRequest;
use App\Models\Student;
use App\Models\User;
use App\Repositories\SectionRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SectionService
{

    /**
     * Summary of authUser
     * @var User
     */
    private $authUser;

    // Repository

    private $sectionRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(SectionRepositoryInterface $sectionRepositoryInterface)
    {
        $this->authUser = Auth::user();
        $this->sectionRepositoryInterface = $sectionRepositoryInterface;
    }

    /**
     * Summary of assign: A public function that assigns the student's section ID
     * @param array $validated
     * @param string $section_id
     * @return void
     */
    public function assign(array $validated = [], string $section_id)
    {
        // Loop student ids
        foreach ($validated['student_ids'] as $student) {

            // Find Student
            $student = Student::find($student['student_id']);

            // Update section_id and save
            $student->section_id = $section_id;
            $student->save();
        }
    }

    /**
     * Summary of get: Get all records
     * @param array $filters
     */
    public function get(array $filters = [])
    {

        // Get
        $query = $this->sectionRepositoryInterface->queryGet($filters);

        // Apply the search filter if a search term is provided
        if (!empty($filters['searchTerm'])) {
            $query->where('name', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%')->orWhere('id', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%');
        }

        // Limit the results to a maximum of 10
        $sections = $query->take(10)->get();

        // Return
        return $sections;
    }

    /**
     * Summary of create: Create a new record
     * @param array $filters
     * @param array $validated
     * @param \App\Http\Requests\SectionRequest $sectionRequest
     */
    public function create(array $filters = [], array $validated = [], SectionRequest $sectionRequest)
    {

        // Check role if coordinator
        if ($this->authUser->hasRole('coordinator') && $filters['requestedBy'] === 'coordinator') {

            // Merge the coordinator by ID
            $validated['coordinator_id'] = $this->authUser->coordinator->user_id;
            $validated['program_id'] = $this->authUser->coordinator->program_id;
        }

        // Check length of imported students and compare to the limit
        if (isset($validated['class_list']) && count($this->sectionRepositoryInterface->getImportedStudentsArray($sectionRequest)) > $validated['limit']) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'The class list exceeds the specified student limit.');
        }

        // Create section
        $record = $this->sectionRepositoryInterface->create($validated);

        // Return
        return $record;
    }
}
