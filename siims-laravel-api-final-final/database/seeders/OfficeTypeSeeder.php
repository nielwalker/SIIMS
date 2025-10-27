<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate table types
        DB::table('office_types')->insert([
            [
                "name" => "Department Office",
                "created_at" => now(),
            ],
            [
                "name" => "Other Office",
                "created_at" => now(),
            ],
        ]);
    }
}
