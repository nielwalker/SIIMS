<?php

namespace Database\Seeders;

use App\Models\EndorsementLetterRequestStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EndorsementLetterRequestStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EndorsementLetterRequestStatus::insert([
            [
                "id" => 1,
                "name" => "Pending",
                "description" => "The request has been submitted and is awaiting the chairperson's review.",
            ],
            [
                "id" => 2,
                "name" => "Pending Approval",
                "description" => "Includes all endorsement letters submitted by the Chairperson that are awaiting the Dean's decision.",
            ],  
            [
                "id" => 3,
                "name" => "Approved",
                "description" => "Lists all endorsement letters that have been reviewed and approved by the Dean.",
            ],
            [
                "id" => 4,
                "name" => "Withdrawn",
                "description" => "Lists all endorsement letters that have been withdrawn due to withdrawing their application.",
            ],
            [
                "id" => 5,
                "name" => "Walk-In",
                "description" => "The endorsement letter request was initiated through a walk-in submission.",
            ],
        ]);
    }
}
