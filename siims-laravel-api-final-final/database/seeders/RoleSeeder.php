<?php

namespace Database\Seeders;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            ['name' => 'admin', 'created_at' => Carbon::now()],
            ['name' => 'chairperson', 'created_at' => Carbon::now()],
            ['name' => 'coordinator', 'created_at' => Carbon::now()],
            ['name' => 'company', 'created_at' => Carbon::now()],
            ['name' => 'dean', 'created_at' => Carbon::now()],
            ['name' => 'osa', 'created_at' => Carbon::now()],
            ['name' => 'student', 'created_at' => Carbon::now()],
            ['name' => 'supervisor', 'created_at' => Carbon::now()],
        ]);
    }
}
