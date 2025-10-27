<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Ensure that there are users and roles
        $users = User::whereNotIn('id', $this->excludeUserIds);
        // $users = User::where('id', '<>', $this->ADMIN_ID, 'and', '<>', $this->DEAN_ID, 'and', '<>', $this->STUDENT_ID)->get();
        $roles = Role::all();

        // Create a dictionary for quick lookup
        $roleDict = $roles->pluck('id', 'name')->toArray();

        foreach ($users as $user) {
            // Decide if the user should be a 'student'
            $isStudent = rand(0, 1) === 1; // Randomly decide if user should be a student

            if ($isStudent) {
                // Assign only the 'student' role
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleDict['student'],
                    'start_date' => now(),
                    'end_date' => now()->addYear(), // Example end date
                ]);

            } else {
                // Determine the role assignment logic
                $assignedRoles = [];
                $roleNames = ['chairperson', 'coordinator', 'company', 'dean', 'osa', 'supervisor'];
                
                if (rand(0, 1) === 1) {
                    // Assign Chairperson with Coordinator role
                    if (array_key_exists('chairperson', $roleDict)) {
                        $assignedRoles[] = $roleDict['chairperson'];
                    }
                    if (array_key_exists('coordinator', $roleDict)) {
                        $assignedRoles[] = $roleDict['coordinator'];
                    }
                } else {
                    // Randomly assign other roles except student
                    $roleNames = array_diff($roleNames, ['student']);
                    $randomRoles = collect($roleNames)->random(rand(1, 3));

                    foreach ($randomRoles as $roleName) {
                        if (array_key_exists($roleName, $roleDict)) {
                            $assignedRoles[] = $roleDict[$roleName];
                        }
                    }

                    // Ensure only one coordinator, dean, and osa are assigned
                    $uniqueRoles = array_unique($assignedRoles);
                    if (count($uniqueRoles) != count($assignedRoles)) {
                        $assignedRoles = array_unique($assignedRoles);
                    }
                }

                // Insert roles for the user
                foreach ($assignedRoles as $roleId) {
                    DB::table('user_roles')->insert([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'start_date' => now(),
                        'end_date' => now()->addYear(), // Example end date
                    ]);
                }
            }
        }

        $this->command->info('User roles have been assigned.');
    }
}
