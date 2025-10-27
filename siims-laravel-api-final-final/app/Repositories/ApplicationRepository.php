<?php

namespace App\Repositories;

use App\Models\Application;
use App\Models\EndorsementLetterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApplicationRepository implements ApplicationRepositoryInterface
{

    /**
     * Summary of authUser
     * @var User|null
     */
    private $authUser;

    // Repository variable
    private $studentRepositoryInterface;
    private $workPostRepositoryInterface;
    private $application;

    /**
     * Create a new class instance.
     */
    public function __construct(StudentRepositoryInterface $studentRepositoryInterface, WorkPostRepositoryInterface $workPostRepositoryInterface, Application $application)
    {
        $this->authUser = Auth::user();
        $this->application = $application;
        $this->studentRepositoryInterface = $studentRepositoryInterface;
        $this->workPostRepositoryInterface = $workPostRepositoryInterface;
    }

    /**
     * Summary of queryApplication: A private function that readies query of application.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function queryApplication()
    {
        return $this->application->query();
    }


    /**
     * Summary of queryWhereStudentID: A private function that queries application where student ID exist.
     * @param string $studentID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function queryWhereStudentID(string $studentID)
    {

        // Initialize query
        $query = $this->queryApplication()->where('student_id', $studentID);

        // Return
        return $query;
    }

    /**
     * Summary of exists
     * @param int $studentID
     * @param int $applicationID
     * @param array $filters
     * @return bool
     */
    public function exists(int $studentID, int $applicationID, array $filters)
    {

        return $this->queryWhereStudentID($studentID)->where('application_id', $applicationID)->exists();
    }

    /**
     * Summary of getPendingApplication: A private function that gets the pending application.
     * @return Application|null
     */
    private function getPendingApplication(): Application|null
    {
        // Check if the student has a pending application
        $pendingApplication = $this->queryApplication()->where('student_id', $this->authUser->id)->where('application_status_id', 1)->first();

        // Return pending application.
        return $pendingApplication;
    }


    /**
     * Summary of findApplicationByWorkPostID: Find application where work post id does exist.
     * @param string $workPostID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findApplicationByWorkPostID(string $workPostID)
    {

        // Find application where work post ID exist.
        $applicationQuery = $this->queryApplication()->where('work_post_id', $workPostID);

        // Return
        return $applicationQuery;
    }

    /**
 * Summary of getByID: A public function that gets application by ID
 * @param array $filters
 * @param string $applicationID
 * @return Application
 */
public function getByID(array $filters = [], string $applicationID): ?Application
{
    // Initialize query
    $query = $this->queryApplication();
    $application = null;

    // Initialize with query relations
    $withRelations = ['documentSubmissions.documentType', 'endorsement', 'workPost', 'endorsement.endorseStudents'];

    if ($this->authUser->hasRole('student') && $filters['requestedBy'] === 'student') {
        $studentID = $this->authUser->student->user_id;

        // Check if the student is the requestor or a participant in the endorsement
        $application = $query
            ->where(function ($q) use ($studentID) {
                $q->where('student_id', $studentID) // Requestor
                  ->orWhereHas('endorsement.endorseStudents', function ($participantQuery) use ($studentID) {
                      $participantQuery->where('student_id', $studentID); // Included student
                  });
            })
            ->with($withRelations) // Make sure to load endorsement relations
            ->find($applicationID);
    }

    // Return the application (could be null if not found)
    return $application;
}





    /**
     * Summary of findByStudentID: A public function that finds the application by studentID.
     * @param string $applicationID
     * @param string $studentID
     * @return \App\Models\Application|null
     */
    public function findByStudentID(string $applicationID, string $studentID): Application|null
    {

        $application = $this->queryWhereStudentID($studentID)->find($applicationID);

        // Check if application does exist.
        if (!$application) {
            abort(Response::HTTP_NOT_FOUND, 'Application not found for this student.');
        }

        // Return
        return $application;
    }

    /**
     * Summary of create: A public function that creates a new application.
     * @param string $workPostID
     * @return Application
     */
    public function create(string $workPostID): Application
    {

        // Check if student_status_id is Not Yet Applied (1)
        if (!($this->studentRepositoryInterface->isNotYetApplied())) {
            abort(Response::HTTP_BAD_REQUEST, 'You are already enrolled in other job.');
        }

        // Initialize last_applied variable
        $lastAppliedAt = $this->studentRepositoryInterface->getLastAppliedAt();

        // Check if there was a previous application and if it was within the last 5 days
        if ($lastAppliedAt && $lastAppliedAt->diffInDays(now()) <= 5) {
            abort(Response::HTTP_BAD_REQUEST, 'You can only apply for a job once every 5 days.');
        }

        // Find Work Post
        $workPost = $this->workPostRepositoryInterface->find($workPostID);

        // Check if work post is expired
        if ($this->workPostRepositoryInterface->isExpired($workPost->end_date)) {
            abort(Response::HTTP_BAD_REQUEST, 'This job posting has expired.');
        }

        /**
         * Prevent Duplicate Applications
         * - Ensure that the student has not already applied to the same job posting.
         */
        // Check if application is already existing
        if ($this->isExistingApplication($workPostID)) {
            abort(Response::HTTP_BAD_REQUEST, 'You have already applied to this job.');
        }

        // Check if the max applicants limit has been reached
        if ($this->workPostRepositoryInterface->isMaxedApplicants($workPost)) {
            abort(Response::HTTP_BAD_REQUEST, 'The job has already reached its maximum number of applicants.');
        }

        // Get pending application
        $pendingApplication = $this->getPendingApplication();

        // Check if the student has a pending application
        if ($pendingApplication) {
            // Check if it is still within 5 days
            $applicationAge =  $pendingApplication->created_at->diffInDays(now());
            if ($applicationAge < 5) {
                abort(Response::HTTP_BAD_REQUEST, 'You have a pending application and cannot apply for another job until 5 days have passed.');
            } else {
                // Allow reapplication and optionally update the previous application status
                $pendingApplication->application_status_id = 7; // Application_status_id -> 3 is "Expired"
                $pendingApplication->remarks = "Automatically marked as expired after 5 days.";
                $pendingApplication->save();

                // Update Student Status to not yet applied
                $this->studentRepositoryInterface->updateToNotYetApplied();
            }
        }

        // Create a new application record
        $application = Application::create(array_merge([
            "work_post_id" => $workPostID,
            'student_id' => $this->authUser->id,
            'remarks' => 'Applying',
        ]));

        // Check if application is created
        if (!$application) {
            abort(Response::HTTP_BAD_REQUEST, 'Unable to create application.');
        }

        // Return application
        return $application;
    }

    /**
     * Summary of isExistingApplication: A public function that checks if it is existing application.
     * @param string $workPostID
     * @return bool
     */
    public function isExistingApplication(string $workPostID): bool
    {
        Log::info('Testing');
        Log::info($this->findApplicationByWorkPostID($workPostID)->where('student_id', $this->authUser->student->user_id)->first());
        return $this->findApplicationByWorkPostID($workPostID)->where('student_id', $this->authUser->student->user_id)->exists();
    }
}
