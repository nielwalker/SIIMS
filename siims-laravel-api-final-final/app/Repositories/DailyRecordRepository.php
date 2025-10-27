<?php

namespace App\Repositories;

use App\Models\DtrEntry;
use Illuminate\Support\Facades\Auth;

class DailyRecordRepository implements DailyRecordRepositoryInterface
{
    
    // Auth User
    private $auth_user;

    // Model
    private $dtrEntry;

    /**
     * Create a new class instance.
     */
    public function __construct(DtrEntry $dtrEntry)
    {
        $this->auth_user = Auth::user();
        $this->dtrEntry = $dtrEntry;
    }

    /**
     * Summary of create: Create a new record
     * @param array $validated
     * @return DtrEntry
     */
    public function create(array $validated) {

        // Create new record
        $dailyRecord = $this->dtrEntry->create($validated);

        // Return
        return $dailyRecord;

    }

    /**
     * Summary of find: Find record by ID.
     * @param string $id
     * @return DtrEntry|null
     */
    public function find(string $id) {

        // Find record
        $record = $this->dtrEntry->find($id);

        // Return
        return $record;

    }
    
    /**
     * Summary of delete: Delete record by ID.
     * @param string $id
     * @return void
     */
    public function delete(string $id) {

        // Find record
        $record = $this->find($id);

        // Delete record
        $record->delete();

    }

    /**
     * Summary of get: Get all records
     * @return \Illuminate\Database\Eloquent\Collection<int, DtrEntry>
     */
    public function get() {
        
        return $this->dtrEntry->where('student_id', $this->auth_user->student->user_id)->get();
    }

    /**
     * Summary of update: Update record by ID.
     * @param array $validated
     * @param string $id
     * @return DtrEntry
     */
    public function update(array $validated=[], string $id) {

        // Find record
        $record = $this->find($id);

        // Update record
        $record->update($validated);

        // Return
        return $record;

    }

    /**
     * Summary of getTotal: Get total count of records.
     * @param string $user_id
     * @return int
     */
    public function getTotal(string $user_id) {

        // Get Total
        return $this->dtrEntry->where('student_id', $user_id)->count();

    }
}
