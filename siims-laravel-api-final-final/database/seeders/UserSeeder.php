<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Company;
use App\Models\Office;
use App\Models\Program;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        # Create 50 users
        User::factory()->count(10)->create();
        
    }
}
