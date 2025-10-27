<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\DailyRecordRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DailyRecordService
{
    // Auth
    /**
     * Summary of authUser
     * @var User
     */
    private $authUser;

    // Repository
    private $dailyRecordRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(DailyRecordRepositoryInterface $dailyRecordRepositoryInterface)
    {
        $this->authUser = Auth::user();
        $this->dailyRecordRepositoryInterface = $dailyRecordRepositoryInterface;
    }

    /**
     * Summary of addIfVerified: Verify role if it is admin.
     * @param array $filters
     * @param array $validated
     * @return array
     */
    private function addIfVerified(array $filters = [], array $validated = []) {
        // Check role if student
        if ($filters['requestedBy'] === 'student' && $this->authUser->hasRole('student')) {
           // Merge
           $validated = array_merge($validated, [
               'student_id' => $this->authUser->student->user_id,
           ]);
       }

       // Return
       return $validated;
   }


    /**
     * Summary of delete: Delete by ID.
     * @param string $id
     * @return void
     */
    public function delete(string $id) {

        // Delete
        $this->dailyRecordRepositoryInterface->delete($id);

    }

    /**
     * Summary of get: Get all records
     */
    public function get()
    {
        // Fetch
        $dailyRecords = $this->dailyRecordRepositoryInterface->get();

        // Return
        return $dailyRecords;
    }

    /**
     * Summary of update: Update record by ID.
     * @param array $filters
     * @param array $validated
     * @param string $id
     */
    public function update(array $filters = [], array $validated = [], string $id) {
        // Check role first
        $validated = $this->addIfVerified($filters, $validated);

        // Update record
        $record = $this->dailyRecordRepositoryInterface->update($validated, $id);

        // Return
        return $record;
    }


    /**
     * Summary of create: Create a new record
     * @param array $filters
     * @param array $validated
     */
    public function create(array $filters = [], array $validated = [])
    {

        // Check role first
        $validated = $this->addIfVerified($filters, $validated);

        // Create Daily Time Record
        $dailyRecord = $this->dailyRecordRepositoryInterface->create($validated);

        // Return
        return $dailyRecord;
    }
}
