<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\WeeklyEntry;
use Illuminate\Support\Facades\Auth;

class WeeklyRecordRepository implements WeeklyRecordRepositoryInterface
{

   
    /**
     * Summary of authUser
     * @var User
     */
    private $authUser;

     /**
     * 
     * 
     * Other Models
     * 
     */
    private $weeklyEntry;

    /**
     * Create a new class instance.
     */
    public function __construct(WeeklyEntry $weeklyEntry)
    {
        $this->authUser = Auth::user();
        $this->weeklyEntry = $weeklyEntry;
    }

    /**
     * Summary of update: Updates the record by ID.
     * @param string $id
     * @param array $validated
     * @return WeeklyEntry
     */
    public function update(string $id = '', array $validated = []) {

        // Find record
        $record = $this->weeklyEntry->find($id);

        // Update record
        $record->update($validated);

        // Save
        $record->save();

        // Return
        return $record;

    }

    /**
     * Summary of delete: Delete by ID.
     * @param string $id
     * @return void
     */
    public function delete(string $id = '') {

        // Find 
        $record = $this->find($id);

        // Delete
        $record->delete();

    }

    /**
     * Summary of find: Find record by ID.
     * @param string $id
     * @return WeeklyEntry
     */
    public function find(string $id = '') {
        // Find
        $record = $this->weeklyEntry->findOrFail($id);

        // Return
        return $record;
    }

    /**
     * Summary of getByStudent: Get by student
     * @param string $student_id
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function getByStudent(string $student_id = '') {

        // Query student's weekly
        $query = $this->weeklyEntry->where('student_id', $student_id);

        // Return
        return $query->get();
    }

    /**
     * Summary of getByCoordinator: Get all weekly entries for a coordinator
     * @param string $coordinator_id
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function getByCoordinator(string $coordinator_id = '') {

        // Query weekly entries for students under this coordinator
        $query = $this->weeklyEntry->whereHas('student', function($q) use ($coordinator_id) {
            $q->where('coordinator_id', $coordinator_id);
        });

        // Return
        return $query->get();
    }

    /**
     * Summary of get: Get the weekly entries
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection<int, WeeklyEntry>
     */
    public function get(array $filters = []) {

        // Initialize query variable
        $query = null;

        // Check role and requested by is student
        if($filters['requestedBy'] === 'student' && $this->authUser->hasRole('student')) {

            $query = $this->weeklyEntry->where('student_id', $this->authUser->student->user_id);
           
        } 

        // Return
        return $query->get();

    }

    /**
     * Summary of create: Create new record.
     * @param array $validated
     * @return WeeklyEntry
     */
    public function create(array $validated = []) {

        // Create new record
        $record = $this->weeklyEntry->create($validated);

        // Return
        return $record;

    }
}
