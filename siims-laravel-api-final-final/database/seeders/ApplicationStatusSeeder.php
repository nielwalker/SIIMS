<?php

namespace Database\Seeders;

use App\Models\ApplicationStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApplicationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         /**
         * Generate Application Statuses
         */
        ApplicationStatus::insert([
            [
                "id" => 1,
                "name" => "Pending",
                "description" => "The student has submitted their application, but it has not yet been reviewed by the employer. This status indicates that the application is waiting to be evaluated."
            ],
            [
                "id" => 2,
                "name" => "Under Review",
                "description" => "The application is being reviewed by the internship coordinator, HR team, or hiring manager. They are assessing the candidate’s qualifications, resume, and cover letter."
            ],
            [
                "id" => 3,
                "name" => "Rejected",
                "description" => "The application has been reviewed and the student has not been selected for the internship position. This status indicates that the employer has decided not to move forward with the candidate."
            ],
            [
                "id" => 4,
                "name" => "Approved",
                "description" => "The company has accepted the student for the internship position. OSA will still review required documents, such as medical clearance and insurance, before approving the deployment."
            ],
            [
                "id" => 5,
                "name" => "Withdrawn",
                "description" => "The student has withdrawn their application for the internship, either due to personal reasons, finding another opportunity, or deciding not to pursue the internship further."
            ],
            [
                "id" => 6,
                "name" => "OJT Completed",
                "description" => "The intern has successfully finished their On-the-Job Training (OJT). This status indicates that the student has completed all their required tasks, training, and assignments during the internship period."
            ],
            [
                "id" => 7,
                "name" => "Immersion Completed",
                "description" => "The student has successfully completed their immersion program. This status indicates that the student has finished all required tasks, activities, or training related to the immersion, and the program has concluded."
            ],
            [
                "id" => 8,
                "name" => "Expired",
                "description" => "The application has automatically expired after the maximum allowable waiting period. This status indicates that the student did not receive a response or approval within the expected time frame."
            ],
            [
                "id" => 9,
                "name" => "Ready For Deployment",
                "description" => "The student has completed all the necessary enrollment and preparatory requirements for the OJT or Immersion program and is now awaiting deployment to their assigned training or immersion location."
            ],
            [
                "id" => 10,
                "name" => "Active",
                "description" => "The student is currently participating in the OJT or Immersion program. This status indicates that the student is engaged in the practical learning or on-the-job training activities."
            ],
        ]);

    }
}
