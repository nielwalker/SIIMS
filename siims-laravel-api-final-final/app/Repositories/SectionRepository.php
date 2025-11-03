<?php

namespace App\Repositories;

use App\Http\Requests\SectionRequest;
use App\Imports\StudentNumbersImport;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class SectionRepository implements SectionRepositoryInterface
{

    // Model
    private $section;

    /**
     * Summary of authUser
     * @var User|null
     */
    private $authUser;

    // Repository variable
    private $logRepositoryInterface;
    private $studentRepositoryInterface;


    /**
     * Create a new class instance.
     */
    public function __construct(Section $section, LogRepositoryInterface $logRepositoryInterface, StudentRepositoryInterface $studentRepositoryInterface)
    {
        $this->section = $section;
        $this->studentRepositoryInterface = $studentRepositoryInterface;
        $this->logRepositoryInterface = $logRepositoryInterface;
        $this->authUser = Auth::user();
    }

    /**
     * Summary of addLog: A private function that creates a new log.
     * @param string $entityID
     * @param string $http_code
     * @param string $actionType
     * @return void
     */
    private function addLog(string $entityID = "", string $http_code, string $actionType)
    {
        // Create new log
        $this->logRepositoryInterface->create(entityID: $entityID, model: Section::class, http_code: $http_code, actionType: $actionType);
    }

    /**
     * Summary of getImportedStudentsArray: A public function that gets the imported students array.
     * @param \App\Http\Requests\SectionRequest $sectionRequest
     * @return array
     */
    public function getImportedStudentsArray(SectionRequest $sectionRequest)
    {
        $file = $sectionRequest->file('class_list');

        // Use the import class to process the file
        $import = new StudentNumbersImport();

        Excel::import($import, $file);

        // Filter out the "Student No" header
        $studentNumbers = array_filter($import->studentNumbers, function ($value) {
            return $value !== "Student No";
        });

        // Re-index the array to make it cleaner
        $studentNumbers = array_values($studentNumbers);

        // Return
        return $studentNumbers;
    }

    /**
     * Summary of query: A private function that starts query.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function query()
    {

        // Start query
        $query = $this->section->query();

        // Return
        return $query;
    }


    /**
     * Summary of queryGet: Starts query get
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryGet(array $filters) {

        // Start query
        $query = $this->query();

        // Check role
        if($this->authUser->hasRole('coordinator') && $filters['requestedBy'] === 'coordinator') {
            // Query Owned by coordinator
            $query->where('coordinator_id', $this->authUser->id);
        }

     
         // Add log
         $this->addLog('N/A', 200, 'View');

         // Return
         return $query;

    }

    /**
     * Summary of get: A public function that gets the sections 10 only.
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get(array $filters)
    {

        // Start query
        $query = $this->queryGet($filters);

        // Limit the results to a maximum of 10
        $sections = $query->take(10)->get();

        // Return
        return $sections;
    }

    /**
     * Summary of create: A public function that creates a new section.
     * @param array $validated
     * @return \App\Models\Section
     */
    public function create(array $validated): Section
    {

        // Create section   
        $section = $this->section->create($validated);

        // Add log
        $this->addLog($section->id, 201, 'Create');

        // Return
        return $section;
    }

    /**
     * Summary of findById: Find a section by ID
     * @param string $id
     * @return \App\Models\Section|null
     */
    public function findById(string $id)
    {
        return $this->section->find($id);
    }

    /**
     * Summary of update: A public function that updates a section.
     * @param string $id
     * @param array $validated
     * @return \App\Models\Section
     */
    public function update(string $id, array $validated): Section
    {
        // Find section
        $section = $this->findById($id);
        
        if (!$section) {
            abort(Response::HTTP_NOT_FOUND, 'Section not found.');
        }

        // Update section
        $section->update($validated);

        // Add log
        $this->addLog($section->id, 200, 'Update');

        // Return
        return $section;
    }

    /**
     * Summary of delete: A public function that deletes a section.
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        // Find section
        $section = $this->findById($id);
        
        if (!$section) {
            abort(Response::HTTP_NOT_FOUND, 'Section not found.');
        }

        // Add log
        $this->addLog($section->id, 200, 'Delete');

        // Delete section
        return $section->delete();
    }

    /**
     * Summary of importAndAddToSection: A public function that imports and adds the student to the section.
     * @param string $id
     * @param \App\Http\Requests\SectionRequest $sectionRequest
     * @return string
     */
    public function importAndAddToSection(string $id, SectionRequest $sectionRequest)
    {

        // Get the array of student numbers
        $studentNumbers = $this->getImportedStudentsArray($sectionRequest);

        // Initialize an array to store IDs of students not found in the system
        $notFound = [];

        foreach ($studentNumbers as $studentNo) {
            // Find the student by their student number
            $student = $this->studentRepositoryInterface->findByUserID($studentNo);

            if ($student) {

                $this->studentRepositoryInterface->updateSectionID($student, $id);
                $this->studentRepositoryInterface->updateCoordinatorID($student, $this->authUser->coordinator->user_id);
            } else {
                // Add to the not found array
                $notFound[] = $studentNo;
            }
        }

        // Convert the $notFound array to a string (comma-separated values)
        $notFound = implode(', ', $notFound);

        // Return
        return $notFound;
    }
}
