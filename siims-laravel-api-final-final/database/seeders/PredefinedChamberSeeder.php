<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PredefinedChamberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            // PredefinedCompanySeeder::class,
            PredefinedChairpersonSeeder::class,
            PredefinedCoordinatorSeeder::class,
            PredefinedStudentSeeder::class,
        ]);
    }
}
