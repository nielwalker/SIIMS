<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends BaseSeeder
{

    protected $UUID_1 = "cac6858d-51e3-4132-9430-37f621b30251";
    protected $UUID_2 = "1476dc58-2d15-44ef-a8ef-34854e40e0ae";

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        Section::insert([
            [
                'id' => $this->UUID_1,
                'program_id' => 8,
                'coordinator_id' => $this->COORDINATOR_ID,
                'name' => '2023-2024 2nd Semester',
                'limit' => 40,
            ],
            [
                'id' => $this->UUID_2,
                'program_id' => 8,
                'coordinator_id' => $this->COORDINATOR_ID,
                'name' => '2024-2025 2nd Semester',
                'limit' => 40,
            ],
        ]);
        
    }
}
