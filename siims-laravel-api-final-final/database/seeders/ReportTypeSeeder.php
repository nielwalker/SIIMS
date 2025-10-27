<?php

namespace Database\Seeders;

use App\Models\ReportType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        ReportType::insert([
            [
                "name" => 'Daily Time Record',
            ],
            [
                "name" => 'Weekly Accomplishment Report',
            ],
            [
                "name" => 'Performance Evaluation',
            ],
            [
                "name" => 'Personal Insights',
            ],
        ]);
    }
}
