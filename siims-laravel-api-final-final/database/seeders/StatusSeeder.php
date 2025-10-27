<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\Student;

class StatusSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate the statuses
        Status::insert([
            [
             
                'name' => 'Pending',
            ],
            [
                'name' => 'Approved',
            ],
            [
                'name' => 'Rejected',
            ],
            [
                'name' => 'Completed',
            ],
            [
                'name' => 'Need Revisions',
            ],
            [
                'name' => 'Withdrawn',
            ],
            [
                'name' => 'Incomplete',
            ],
            [
                'name' => 'Not yet applied'
            ],
            [
                'name' => 'Applying',
            ],
            [
                'name' => 'Accepted',
            ],
            [
                'name' => 'Ready for deployment',
            ],
            [
                'name' => 'Ongoing Intern/Immersion'
            ],
            [
                'name' => 'Waiting for approval',
            ],
        ]);

        // For Student Update Status
        Student::where('id', $this->STUDENT_ID)->update([
            "status_id" => 8 // not yet applied
        ]);
    }
}
