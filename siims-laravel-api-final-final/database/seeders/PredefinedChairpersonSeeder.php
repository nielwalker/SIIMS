<?php

namespace Database\Seeders;

use App\Models\Coordinator;
use App\Models\Program;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PredefinedChairpersonSeeder extends Seeder
{   

        
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get chairpersons
        $chairpersons = $this->fetchChairpersons();

         /**
         * - Loop cooordinators
         * - Add new User and their role (Chairperson)
         * - Assign to a Program (BSIT)
         */
        foreach($chairpersons as $chairperson) {

            // Create user
            $user = User::updateOrCreate([
                "id" => $chairperson['id'],
                "first_name" => $chairperson['first_name'],
                "last_name" => $chairperson['last_name'],
                "email" => $chairperson['email'],
                "password" => $chairperson['password'],
            ]);

            // var_dump($user);

            // Create user role
            UserRole::create([
                'user_id' => $user->id,
                "role_id" => 2,
            ]);

            // Create user role
            UserRole::create([
                'user_id' => $user->id,
                "role_id" => 3,
            ]);

            // Find program by ID
            $program = Program::findOrFail($chairperson['program_id']);

            // Create coordinator
            Coordinator::create([
                "id" => $user->id,
                "user_id" => $user->id,
                "program_id" => $chairperson['program_id'],
            ]);

            /**
             * - Assign chairperson to a program
             * - Updated and save
             */
            $program->chairperson_id = $user->id;
            $program->save();

        }
    }

    /**
     * Summary of fetchChairpersons: A private function that gets all list of chairpersons (2025)
     * @return array
     */
    private function fetchChairpersons() {

        // Array of chairpersons
        $chairpersons = [
            [
                "id" => 3060555,
                "first_name" => "Jay Noel",
                "last_name" => "Rojo",
                "email" => "jaynoel.rojo@ustp.edu.ph",
                "password" => bcrypt("siimsJayNoelRojo"),
                "program_id" => 8, 
            ],
        ];


        // Return 
        return $chairpersons;
    }
}
