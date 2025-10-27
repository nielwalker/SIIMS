<?php

namespace Database\Seeders;

use App\Models\StudentStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Generate Student Status
         */
        StudentStatus::insert([
            [
                "name" => 'Not Yet Enrolled',
                "description" => "The student has not yet enrolled or applied for the OJT or Immersion program. They may still be in the process of preparation or considering their participation."
            ],
            [
                "name" => "Pending Approval",
                "description" => "The student's enrollment or participation in the OJT or Immersion program is awaiting approval. This could mean the application is still under review or requires final confirmation."
            ],
            [
                "name" => "Enrolled",
                "description" => "The student has officially enrolled in the OJT or Immersion program, but has not yet started the training or immersion process."
            ],
            [
                "name" => "Ready For Deployment",
                "description" => "The student has completed all the necessary enrollment and preparatory requirements for the OJT or Immersion program and is now awaiting deployment to their assigned training or immersion location."
            ],
            [
                "name" => "Active",
                "description" => "The student is currently participating in the OJT or Immersion program. This status indicates that the student is engaged in the practical learning or on-the-job training activities."
            ],
            [
                "name" => "Completed",
                "description" => "The student has finished the OJT or Immersion program. They have successfully completed the required tasks, learning modules, or practical activities and have met all program requirements."
            ],
            /* [
                "name" => "Dropped Out",
                "description" => "The student has voluntarily discontinued their participation in the OJT or Immersion program, typically before completion. This could be due to personal reasons, conflicting schedules, or academic changes."
            ],
            [
                "name" => "Suspended",
                "description" => "The student has been temporarily suspended from the OJT or Immersion program due to disciplinary actions, failure to meet program requirements, or other academic or behavioral issues."
            ],
            [
                "name" => "Expelled",
                "description" => "The student has been permanently removed from the OJT or Immersion program, usually due to severe disciplinary issues, failure to meet program standards, or violation of terms and conditions."
            ],
            [
                "name" => "Failed",
                "description" => "The student did not meet the necessary performance or learning standards to successfully complete the OJT or Immersion program. This could be due to poor performance or failure to meet expectations."
            ], */
        ]);
        
    }
}
