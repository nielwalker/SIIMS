<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate the document types
        DB::table('document_types')->insert([
            [
                "id" => 1,
                "name" => "Cover Letter",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 2,
                "name" => "Endorsement Letter",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 3,
                "name" => "Resume",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 4,
                "name" => "Medical Certificate",
                "document_step_id" => 2,
                "created_at" => now(),
            ],
            [   
                "id" => 5,
                "name" => "Acceptance Letter",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 6,
                "name" => "Letter of Intent",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 7,
                "name" => "Notarized Written Letter",
                "document_step_id" => 2,
                "created_at" => now(),
            ],
            [
                "id" => 8,
                "name" => "Internship Contract/Agreement",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 9,
                "name" => "Application Letter",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 10,
                "name" => "Transcript Records",
                "document_step_id" => 2,
                "created_at" => now(),
            ],
            [
                "id" => 11,
                "name" => "Certificate of Enrollment",
                "document_step_id" => 2,
                "created_at" => now(),
            ],
            [
                "id" => 12,
                "name" => "Certificate of Registration",
                "document_step_id" => 2,
                "created_at" => now(),
            ],
            [
                "id" => 13,
                "name" => "Certificate of Orientation",
                "document_step_id" => 1,
                "created_at" => now(),
            ],
            [
                "id" => 14,
                "name" => "Internship Contract",
                "document_step_id" => 2,
                "created_at" => now(),
            ],


            [
                "id" => 15,
                "name" => "Daily Time Record",
                "document_step_id" => 3,
                "created_at" => now(),
            ],
            [
                "id" => 16,
                "name" => "Weekly Accomplishment Report",
                "document_step_id" => 3,
                "created_at" => now(),
            ],
            [
                "id" => 17,
                "name" => "Performance Evaluation",
                "document_step_id" => 3,
                "created_at" => now(),
            ],
            [
                "id" => 18,
                "name" => "Personal Insights",
                "document_step_id" => 3,
                "created_at" => now(),
            ],
        ]);
    }
}
