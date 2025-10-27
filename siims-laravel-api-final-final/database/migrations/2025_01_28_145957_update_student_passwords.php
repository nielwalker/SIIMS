<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fetch all students and their associated users
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('students.id as student_id', 'users.id as user_id')
            ->get();

        foreach ($students as $student) {
            // Update the user's password to "password" (if null or already set)
            DB::table('users')
                ->where('id', $student->user_id)
                ->update(['password' => bcrypt('password')]);

            // Insert the student's user_id into user_roles (role_id = 7 for students)
            DB::table('user_roles')->insert([
                'user_id' => $student->user_id,
                'role_id' => 7, // Assuming 7 is the role ID for students
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally, remove the inserted records from user_roles
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('users.password', bcrypt('password'))
            ->select('students.id as student_id', 'users.id as user_id')
            ->get();

        foreach ($students as $student) {
            // Remove the role entry from user_roles
            DB::table('user_roles')
                ->where('user_id', $student->user_id)
                ->delete();

            // Optionally reset the password to null
            DB::table('users')
                ->where('id', $student->user_id)
                ->update(['password' => null]);
        }
    }
};
