<?php

namespace Database\Seeders;

use App\Models\Coordinator;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoordinatorSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users that has a role of Coordinator
        $users = User::join('user_roles', 'users.id', '=', 'user_roles.user_id')
        ->join('roles', 'roles.id', '=', 'user_roles.role_id')
        ->where('roles.name', 'coordinator')
        ->select(['users.id'])
        ->get();

        // Insert new coordinator in the table named coordinator
        foreach($users as $user) {

            if($this->COORDINATOR_ID == '20193471861') {
                continue;
            }

            Coordinator::insert([
                "id" => $user['id'],
                "user_id" => $user['id'],
            ]);
        }
    }
}
