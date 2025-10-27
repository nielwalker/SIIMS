<?php

namespace App\Repositories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentRepository implements StudentRepositoryInterface
{

    // Auth
    /**
     * Summary of authUser
     * @var User
     */
    private $authUser;

    // Model variable
    private $student;

    /**
     * Create a new class instance.
     */
    public function __construct(Student $student)
    {
        $this->authUser = Auth::user();
        $this->student = $student;
    }

    /**
     * Summary of queryAllStudentsFromProgram: Get query all students by program ID.
     * @param string $programID
     * @return Student
     */
    public function queryAllStudentsFromProgram(String $programID) {

        return $this->student->where('program_id', $programID);

    }

    /**
     * Summary of get: Get students query.
     * @param array $filters
     * @return Student|\Illuminate\Database\Eloquent\Builder
     */
    public function get(array $filters = []) {

        // Initialize query variable
        $query = $this->student;
    
        // ADMIN
        if($this->authUser->hasRole('admin') && $filters['requestedBy'] === 'admin') {

            $query = $this->student->with(['user', 'program.college', 'status', 'studentStatus', 'coordinator.user', 'company'])->withCount(['applications', 'endorsementLetterRequests']);
        }

        // Coordinator
        else if($this->authUser->hasRole('coordinator') && $filters['requestedBy'] === 'coordinator') {
            
            $query = $this->student->with(['user', 'program.college', 'studentStatus', 'company', 'latestApplication', 'applications.applicationStatus', 'applications.workPost.office.company', 'certificates'])->where('coordinator_id', '=', $this->authUser->coordinator->user_id)->withCount(['applications']);
        }

        // Dean
        else if($this->authUser->hasRole('dean') && $filters['requestedBy'] === 'dean') {
            $query = $this->student->with(['user', 'program', 'status', 'coordinator.user', 'company'])->withCount(['applications', 'endorsementLetterRequests'])->whereHas('program.college', function ($query) {
                $query->where('dean_id', $this->authUser->id);
            });
        }

        // Chairperson
        else if($this->authUser->hasRole('chairperson') && $filters['requestedBy'] === 'chairperson') {
            $query = $this->student->with(['user', 'status', 'coordinator.user', 'studentStatus', 'company'])->withCount(['applications', 'endorsementLetterRequests'])->whereHas('program', function ($query) {
                $query->where('chairperson_id', $this->authUser->id);
            });
        }

        // Company
        else if($this->authUser->hasRole('company') && $filters['requestedBy'] === 'company') {
            $query = $this->student->with(['user', 'program', 'status', 'coordinator.user', 'company']);
        }

        // Get Search Term
        $searchTerm = $filters['searchTerm'];

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Sort by last name
        $query->with('user')->join('users', 'students.user_id', '=', 'users.id')
        ->orderBy('users.first_name')
            ->orderBy('users.last_name');

        // Return
        return $query;

    }

    /**
     * Summary of getTotalStudents: A public function that gets the total of students.
     * @return int
     */
    public function getTotalStudents() {
        return $this->student->count();
    }

    /**
     * Summary of findByUserID: Find the student by user ID.
     * @param string $id
     * @return \App\Models\Student|null
     */
    public function findByUserID(string $id): Student|null {
        return $this->student->where('user_id', $id)->first();
    }

    /**
     * Summary of updateSectionID: A public function that updates the section ID.
     * @param \App\Models\Student $student
     * @param string $sectionID
     * @return void
     */
    public function updateSectionID(Student $student, string $sectionID) {
        $student->section_id = $sectionID;
        $student->save();
    }

    /**
     * Summary of updateCoordinatorID: A public function that updates the student's coordinator by ID.
     * @param \App\Models\Student $student
     * @param string $coordinatorID
     * @return void
     */
    public function updateCoordinatorID(Student $student, string $coordinatorID) {
        $student->coordinator_id = $coordinatorID;
        $student->save();
    }

    /**
     * Summary of isNotYetApplied: A public function that checks if the student is not yet applied.
     * @return bool
     */
    public function isNotYetApplied() {
        
        return $this->authUser->student->student_status_id === 1;
    }

    /**
     * Summary of getLastAppliedAt: A public function that gets the last applied at (date) from student.
     * @return mixed
     */
    public function getLastAppliedAt() {
        return $this->authUser->student->last_applied_at;
    }

    /**
     * Summary of updateStatus: A private function that updates the student base on the given status ID.
     * @param int $statusID
     * @return void
     */
    private function updateStatus(int $statusID) {

        $this->authUser->student->student_status_id = $statusID;
    
        $this->authUser->student->save();
    }

    /**
     * Summary of updateToPending: A public function that updates the student to pending.
     * @return void
     */
    public function updateToPending() {
        $this->updateStatus(2);
    }

    /**
     * Summary of updateToNotYetApplied: A public function that updates the student to not yet applied.
     * @return void
     */
    public function updateToNotYetApplied() {
        $this->updateStatus(1);
    }

    /**
     * Summary of updateLastAppliedAtToNow: A public function that updates the last applied at date to now
     * @return void
     */
    public function updateLastAppliedAtToNow() {
        $this->authUser->student->last_applied_at = now();
        $this->authUser->student->save();
    }
}
