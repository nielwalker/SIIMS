<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate a work type seeder
        DB::table('work_types')->insert([
            [
                "name" => "internship",
                "created_at" => now(),
            ],
            [
                "name" => "immersion",
                "created_at" => now(),
            ]
        ]);
    }
}
