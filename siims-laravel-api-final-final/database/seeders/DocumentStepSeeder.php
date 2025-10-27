<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentStepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate the document step
        DB::table('document_steps')->insert([
            [
                "name" => "Step 1",
                "created_at" => now(),
            ],
            [
                "name" => "Step 2",
                "created_at" => now(),
            ],
            [
                "name" => "Step 3",
                "created_at" => now(),
            ],
        ]);
    }
}
