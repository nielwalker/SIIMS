<?php

namespace App\Services;

use App\Models\EndorsementLetterRequest;
use App\Models\User;
use App\Repositories\EndorsementLetterRequestRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EndorsementLetterRequestService
{

    // Auth
    /**
     * Summary of authUser
     * @var User|null
     */
    private $authUser;

    // Repository
    private $endorsementLetterRequestRepositoryInterface;

    // Service
    private $programService;

    /**
     * Create a new class instance.
     */
    public function __construct(EndorsementLetterRequestRepositoryInterface $endorsementLetterRequestRepositoryInterface, ProgramService $programService)
    {

        $this->authUser = Auth::user();
        $this->endorsementLetterRequestRepositoryInterface = $endorsementLetterRequestRepositoryInterface;
        $this->programService = $programService;
    }
    
    /**
     * Summary of checkIfExist: Check if exist.
     * @param \App\Models\EndorsementLetterRequest $endorsementLetterRequest
     * @return void
     */
    private function checkIfExist(EndorsementLetterRequest $endorsementLetterRequest) {

        // Check if does not exist
        if (!$endorsementLetterRequest) {
            abort(RESPONSE::HTTP_NOT_FOUND, 'Endorsement letter request not found');
        }

    }

    /**
     * Summary of restoreByID: A public function that restores a record.
     * @param string $id
     * @return void
     */
    public function restoreByID(string $id) {

         // Find Delete by ID
         $endorsementRequest = $this->endorsementLetterRequestRepositoryInterface->findArchive($id);

         // Check if does not exist
         $this->checkIfExist($endorsementRequest);


        // Restore record
        $endorsementRequest->restore();


    }

    /**
     * Summary of delete: A public function that deletes the endorsement letter request by ID.
     * @param string $id
     * @return void
     */
    public function delete(string $id)
    {

        // Find Delete by ID
        $endorsementRequest = $this->endorsementLetterRequestRepositoryInterface->find($id);

        // Check if does not exist
        $this->checkIfExist($endorsementRequest);

        // Delete record
        $endorsementRequest->delete();
    }

    /**
     * Summary of getArchives: Get archives only
     * @param array $filters
     */
    public function getArchives(array $filters)
    {
        // Return query archives
        return $this->endorsementLetterRequestRepositoryInterface->getArchives($filters);
    }

    /**
     * Summary of get: A function that gets the query for endorsement letter requests.
     * @param array $filters
     * @return mixed
     */
    public function get(array $filters)
    {

        // FOR CHAIRPERSON
        if ($this->authUser->hasRole('chairperson') && $filters['requestedBy']) {

            // Fetch program for chairperson if exist
            $program = $this->programService->getProgramIfChairpersonExist($this->authUser->id);

            // Merge filter
            $filters = array_merge($filters, [
                'program_id' => $program->id,
            ]);
        }

        // Initiazlie query
        $query = null;

        // Check filter status is archived
        if ($filters['status'] === 'archived') {
            // dd($filters);
            $query = $this->getArchives($filters);
        } else {
            // Get query
            $query = $this->endorsementLetterRequestRepositoryInterface->get($filters);

            // Apply Date filter if provided
            if (!empty($filters['date'])) {
                $query->whereDate('created_at', '=', $filters['date']);
            }
        }

        // Apply Search filter  
        if (!empty($filters['searchTerm'])) {
            $query->whereHas('student.user', function ($query) use ($filters) {
                $query->where('first_name', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%')->orWhere('middle_name', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%')->orWhere('last_name', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%')->orWhere('email', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%');
            })->orWhere('company_name', 'LIKE', '%' . strtolower($filters['searchTerm']) . '%');
          
        }

        // Add order by created_at (default to descending order)
        $query->orderBy('created_at', 'asc'); // You can change 'desc' to 'asc' for ascending order

        // Paginate the results
        $endorsementLetterRequests = $query->paginate($filters['perPage']);

        // Return
        return $endorsementLetterRequests;
    }

    /**
     * Summary of create: Create new record of request.
     * @param array $validated
     * @param array $filters
     * @return \App\Models\EndorsementLetterRequest
     */
    public function create(array $validated, array $filters): EndorsementLetterRequest
    {


        // Check filter (type) is equal to Manual.
        if ($filters['type'] === 'manual') {

            // Merge array
            $validated = array_merge($validated, [
                'endorsement_letter_request_status_id' => 5,
                'type' => 'walk-in',
            ]);

            if (!empty($validated['student_ids'])) {
                // Filter out student IDs where student_id is equal to requestedBy_id
                $validated['student_ids'] = array_filter($validated['student_ids'], function ($student) use ($validated) {
                    return $student['student_id'] !== $validated['requested_by_id'];
                });
            }
        }

        // Auto
        else {
            // Check if endorsement letter already exists
            if ($this->endorsementLetterRequestRepositoryInterface->exists($this->authUser->student->user_id, $validated['work_post_id'])) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'You already have requested an endorsement letter for this job post.');
            }
        }

        // Check if requested_by_id or student_Ids already request an endorsement letter
        /* if ($this->authUser->hasRole('admin') && $this->endorsementLetterRequestRepositoryInterface->existsWithinLastFiveDays($validated['requested_by_id'], $validated['student_ids'])) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'You can only request a new endorsement letter after 5 days from the last request.');
        } */

        // Create endorsement letter request
        $endorsementLetterRequest = $this->endorsementLetterRequestRepositoryInterface->create($validated, $filters);

        // Return
        return $endorsementLetterRequest;
    }
}
