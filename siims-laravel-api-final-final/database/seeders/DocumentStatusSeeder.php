<?php

namespace Database\Seeders;

use App\Models\DocumentStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Generate Document Status
         */
        DocumentStatus::insert([
            [
                "name" => "Pending",
                "description" => "The student has not yet submitted the required document(s). This status indicates that the submission is awaited."
            ],
            [
                "name" => "Submitted",
                "description" => "The student has submitted the required document(s), but the program coordinator or school is yet to review or verify it."
            ],
            [
                "name" => "Under Review",
                "description" => "The submitted document(s) are being reviewed by the OSA. This status indicates that the document(s) are being checked for completeness, accuracy, and compliance with program requirements."
            ],
            [
                "name" => "Approved",
                "description" => "The submitted document(s) have been reviewed and approved by the responsible authority. The student has successfully met the document submission requirement."
            ],
            [
                "name" => "Rejected",
                "description" => "The submitted document(s) have been reviewed but were found to be incomplete, inaccurate, or not in compliance with the program’s requirements. The student needs to make corrections or resubmit the document(s)."
            ],
            [
                "name" => "Resubmitted",
                "description" => "The student has resubmitted the document(s) after making the required revisions or additions based on feedback."
            ],
        ]);
    }
}
