<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Specify the IDs to exclude
        $excludeUserIds = [$this->STUDENT_ID,];

        // Get user that has a role for student
        $users = User::join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('roles.name', 'student',)
            ->whereNotIn('users.id', $excludeUserIds)
            ->select('users.id')
            ->get();

        // Get all programs
        $programs = Program::all();

        // Shuffle programs to randomize assignments
        $programs = $programs->shuffle();

        // Assign each student to a program
        foreach ($users as $user) {

            if ($user['id'] !== $this->STUDENT_ID) {
                // Random pick a program for each student
                $program = $programs->random();

                // Create the student record into the tabe
                Student::create([
                    'id' => $user['id'],
                    'user_id' => $user['id'],
                    'program_id' => $program['id'],
                    'status_id' => 8, // Not yet applied
                ]);
            }
        }
    }
}
