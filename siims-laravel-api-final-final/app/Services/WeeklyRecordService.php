<?php

namespace App\Services;

use App\Models\User;
use App\Models\WeeklyEntry;
use App\Repositories\WeeklyRecordRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WeeklyRecordService
{

    /**
     * Summary of authUser
     * @var User
     */
    private $authUser;

    // Repositories
    private $weeklyRecordRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(WeeklyRecordRepositoryInterface $weeklyRecordRepositoryInterface)
    {
        $this->authUser = Auth::user();
        $this->weeklyRecordRepositoryInterface = $weeklyRecordRepositoryInterface;
    }

    /**
     * Summary of checkRole: Check if role is validated to use the action.
     * @param string $requestedBy
     * @return bool
     */
    private function checkRole(string $requestedBy = "", string $authRole = "")
    {

        return $requestedBy === 'student' && $this->authUser->hasRole($authRole);
    }

    /**
     * Summary of delete: Delete by ID.
     * @param string $id
     * @return void
     */
    public function delete(string $id = "") {
        
        // Find 
        $record = $this->weeklyRecordRepositoryInterface->find($id);

        // Check
        if(!$record) {
            abort(Response::HTTP_NOT_FOUND, 'Weekly Record not found.');
        }

        // Delete
        $this->weeklyRecordRepositoryInterface->delete($record->id);

    }

    /**
     * Summary of update: Update by ID.
     * @param string $id
     * @param array $validated
     */
    public function update(string $id = "", array $validated = [])
    {
        // Find the existing record to get student_id
        $existingRecord = $this->weeklyRecordRepositoryInterface->find($id);
        if (!$existingRecord) {
            abort(Response::HTTP_NOT_FOUND, 'Weekly Record not found.');
        }

        // Check if the new week already has 5 reports (excluding the current one)
        $weekNumber = $validated['week_number'] ?? null;
        $studentId = $validated['student_id'] ?? $existingRecord->student_id ?? null;
        if ($weekNumber && $studentId) {
            $existingReportsCount = WeeklyEntry::where('student_id', $studentId)
                ->where('week_number', $weekNumber)
                ->where('id', '!=', $id) // Exclude current report
                ->count();
            
            if ($existingReportsCount >= 5) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'This week is already full. You can only have up to 5 reports per week.');
            }
        }

        // Update
        $record = $this->weeklyRecordRepositoryInterface->update($id, $validated);

        // Return
        return $record;
    }

    /**
     * Summary of getByStudent: Get by student.
     * @param string $student_id
     */
    public function getByStudent(string $student_id) {

        // GET
        $records = $this->weeklyRecordRepositoryInterface->getByStudent($student_id);

        // Return
        return $records;

    }

    /**
     * Summary of getByCoordinator: Get all weekly entries for a coordinator.
     * @param string $coordinator_id
     */
    public function getByCoordinator(string $coordinator_id) {

        // GET
        $records = $this->weeklyRecordRepositoryInterface->getByCoordinator($coordinator_id);

        // Return
        return $records;

    }

    /**
     * Summary of get: Get the records
     * @param array $filters
     */
    public function get(array $filters = [])
    {

        // Get
        $records = $this->weeklyRecordRepositoryInterface->get($filters);

        // Return
        return $records;
    }

    /**
     * Summary of create: Create new record
     * @param array $validated
     * @param array $filters
     */
    public function create(array $validated = [], array $filters = [])
    {

        // Check role if student
        if ($this->checkRole($filters['requestedBy'], 'student')) {

            // Add new key (student_id)
            $validated['student_id'] = $this->authUser->student->user_id;
        }

        // Check if this week already has 5 reports for this student
        $weekNumber = $validated['week_number'] ?? null;
        $studentId = $validated['student_id'] ?? null;
        if ($weekNumber && $studentId) {
            $existingReportsCount = WeeklyEntry::where('student_id', $studentId)
                ->where('week_number', $weekNumber)
                ->count();
            
            if ($existingReportsCount >= 5) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'This week is already full. You can only add up to 5 reports per week.');
            }
        }

        // Create new record
        $record = $this->weeklyRecordRepositoryInterface->create($validated);

        // Return
        return $record;
    }
}
