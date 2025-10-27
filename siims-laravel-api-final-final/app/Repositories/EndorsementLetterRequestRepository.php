<?php

namespace App\Repositories;

use App\Http\Requests\EndorsementLetterRequests;
use App\Models\EndorsementLetterRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EndorsementLetterRequestRepository implements EndorsementLetterRequestRepositoryInterface
{

    // Auth
    /**
     * Summary of authUser
     * @var User|null
     */
    private $authUser;

    // Model
    private $endorsementLetterRequest;

    // Repository
    private $endorseStudentRepositoryInterface;
    private $programRepositoryInterface;
    private $studentRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(EndorsementLetterRequest $endorsementLetterRequest, EndorseStudentRepositoryInterface $endorseStudentRepositoryInterface, ProgramRepositoryInterface $programRepositoryInterface, StudentRepositoryInterface $studentRepositoryInterface)
    {

        $this->authUser = Auth::user();
        $this->endorsementLetterRequest = $endorsementLetterRequest;
        $this->endorseStudentRepositoryInterface = $endorseStudentRepositoryInterface;
        $this->programRepositoryInterface = $programRepositoryInterface;
        $this->studentRepositoryInterface = $studentRepositoryInterface;
    }

    /**
     * Summary of queryEndorsementLetterRequest: A private function that initializes the query.
     */
    private function queryEndorsementLetterRequest()
    {
        return $this->endorsementLetterRequest->query();
    }

    /**
     * Summary of queryByRole: Queries record.
     * @param array $filters
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return Builder
     */
    private function queryByRole(array $filters, Builder $builder)
    {

        // FOR CHAIRPERSON
        if ($this->authUser->hasRole('chairperson') && $filters['requestedBy'] === 'chairperson') {
            // Get program
            $program = $this->programRepositoryInterface->findChairperson($this->authUser->id);

            // Pluck ids
            $students = $this->studentRepositoryInterface->queryAllStudentsFromProgram($program->id)->pluck('user_id');

            // Fetch endorsement letter requests for the students in the program
            $query = $builder->whereIn('requested_by_id', $students)
                ->with(['student', 'status', 'endorseStudents.student.user', 'application.workPost', 'endorsementLetterRequestStatus'])->withCount(['endorseStudents']);
        } else if ($this->authUser->hasRole('admin') && $filters['requestedBy'] === 'admin') {
            $students = $this->studentRepositoryInterface->get($filters)->pluck('user_id');

            $query = $builder->whereIn('requested_by_id', $students)
                ->with(['student', 'status', 'endorseStudents.student.user', 'application.workPost', 'endorsementLetterRequestStatus'])->withCount(['endorseStudents']);
        }


        // Return query
        return $query;
    }

    /**
     * Summary of getArchives: Queries only the deleted endorsement letter request record.
     * @param array $filters
     * @return Builder<mixed>
     */
    public function getArchives(array $filters)
    {

        // Initialize query
        $query = EndorsementLetterRequest::onlyTrashed();

        // Plug query statement
        $query = $this->queryByRole($filters, $query);

        // Return query
        return $query;
    }

    /**
     * Summary of get: Get all students
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function get(array $filters)
    {

        // Start the query
        $query = EndorsementLetterRequest::query();

        // Call function
        $query = $this->queryByRole($filters, $query);

        // Check status
        if ($filters['status'] !== 'all') {
            $query->whereHas('endorsementLetterRequestStatus', function ($query) use ($filters) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($filters['status'])]);
            });
        }

        // Return
        return $query;
    }

    /**
     * Summary of existsWithinLastFiveDays: Fetch if endorsement letter exist...
     * @param string $requestedByID
     * @param array $studentIds
     * @return bool
     */
    public function existsWithinLastFiveDays(string $requestedByID, array $studentIds): bool
    {
        return $this->endorsementLetterRequest
            ->where('requested_by_id', $requestedByID)
            ->where('created_at', '>=', now()->subDays(5)) // Check within the last 5 days
            ->orWhereHas('endorseStudents', function ($query) use ($studentIds) {
                $query->whereIn('student_id', $studentIds) // Match any of the provided student IDs
                    ->where('created_at', '>=', now()->subDays(5)); // Ensure it's within the last 5 days
            })
            ->exists(); // Return true if any records are found
    }


    /**
     * Summary of findArchive: Find deleted record by ID.
     * @param string $id
     * @return mixed|null
     */
    public function findArchive(string $id) {
        // Return find endorsement letter request
        return $this->endorsementLetterRequest->onlyTrashed()->find($id);
    }

    /**
     * Summary of find: A public function that gets the record by ID.
     * @param string $id
     * @return TModel|null
     */
    public function find(string $id)
    {

        // Return find endorsement letter request
        return $this->endorsementLetterRequest->find($id);
    }

    /**
     * Summary of exists: A public function that checks if the endorsement letter does exist.
     * @param string $requestedByID
     * @param string $workPostID
     * @return bool
     */
    public function exists(string $requestedByID, string $workPostID)
    {

        // Find endorsement letter by work post ID.
        return $this->queryEndorsementLetterRequest()->where('requested_by_id', $requestedByID)->orWhereHas('endorseStudents', function ($query) use ($requestedByID) {
            $query->where('student_id', $requestedByID);
        })->where('work_post_id', $workPostID)->exists();
    }

    /**
     * Summary of create: A public function that creates a new record of endorsement letter requests
     * @param array $validated
     * @return EndorsementLetterRequest
     */
    public function create(array $validated, array $filters): EndorsementLetterRequest
    {

        // Initialize null
        $endorsementLetterRequest =  null;

        // Check filters
        if ($filters['type'] === 'manual') {

            // Create Endorsement letter request
            $endorsementLetterRequest = $this->queryEndorsementLetterRequest()->create($validated);
        } else {

            // Create endorsement letter
            $endorsementLetterRequest = $this->queryEndorsementLetterRequest()->create([
                'requested_by_id' => $this->authUser->student->user_id,
                'application_id' => $validated['application_id'],
                'description' => $validated['description'],
                'work_post_id' => $validated['work_post_id'],
            ]);
        }

        // Including adding the student ids if there is...
        $this->endorseStudentRepositoryInterface->create($validated['student_ids'], $endorsementLetterRequest->id);

        // Return 
        return $endorsementLetterRequest;
    }
}
