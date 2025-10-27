<?php

namespace Database\Seeders;
use App\Models\Coordinator;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class PredefinedCoordinatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get coordinators
        $coordinators = $this->getCoordinators();

        foreach ($coordinators as $coordinator) {
            // Check if the user with this ID already exists
            $existingUser = User::find($coordinator['id']);

            if ($existingUser) {

                // Create coordinator
                Coordinator::create([
                    "id" => $coordinator['id'],
                    "user_id" => $coordinator['id'],
                    "program_id" => $coordinator['program_id'],
                ]);

                // Check if role already exists for the user
                $existingRole = UserRole::where('user_id', $coordinator['id'])->where('role_id', 3)->exists();

                // Create User Role
                
                // If user exists, skip creating or updating and log the skipped user
                echo "Skipping user with ID: {$coordinator['id']} (already exists)\n";
                continue;
            }

            // Create user
            $user = User::create([
                "id" => $coordinator['id'],
                "first_name" => $coordinator['first_name'],
                "last_name" => $coordinator['last_name'],
                "email" => $coordinator['email'],
                "password" => bcrypt($coordinator['password']),
            ]);

            // Check if role already exists for the user
            $existingRole = UserRole::where('user_id', $user->id)->where('role_id', 3)->exists();

            if (!$existingRole) {
                // Create user role
                UserRole::create([
                    'user_id' => $user->id,
                    "role_id" => 3, // Coordinator role
                ]);
            }

            // Check if coordinator already exists
            $existingCoordinator = Coordinator::find($user->id);

            if (!$existingCoordinator) {
                // Create coordinator
                Coordinator::create([
                    "id" => $user->id,
                    "user_id" => $user->id,
                    "program_id" => $coordinator['program_id'],
                ]);
            }

            echo "Added coordinator: {$coordinator['first_name']} {$coordinator['last_name']} (ID: {$coordinator['id']})\n";
        }
    }

    /**
     * Summary of getCoordinators: A private function to get all list of coordinators (2025)
     * @return array
     */
    private function getCoordinators()
    {
        // Array of coordinators
        return [
            /* [
                "id" => 3060555,
                "first_name" => "Jay Noel",
                "last_name" => "Rojo",
                "email" => "jaynoel.rojo@ustp.edu.ph",
                "password" => "siimsJayNoelRojo",
                "program_id" => 8,
            ], */
            [
                "id" => 3152402,
                "first_name" => "Jc Vanny Mill",
                "last_name" => "Saledaien",
                "email" => "jcvannymill.saledaien@ustp.edu.ph",
                "password" => "siimsJcVannyMillSaledaien",
                "program_id" => 8,
            ],
            [
                "id" => 3152280,
                "first_name" => "Sigfred",
                "last_name" => "Tong",
                "email" => "sigfred.tong@ustp.edu.ph",
                "password" => "siimsSigfredTong",
                "program_id" => 8,
            ],
            [
                "id" => 3213937,
                "first_name" => "Charlane",
                "last_name" => "Vallar",
                "email" => "charlane.vallar@ustp.edu.ph",
                "password" => "siimsCharlaneVallar",
                "program_id" => 8,
            ],
            [
                "id" => 3213725,
                "first_name" => "Jhon Harvey",
                "last_name" => "Babia",
                "email" => "jhonharvey.babia@ustp.edu.ph",
                "password" => "siimsJhonHarveyBabia",
                "program_id" => 8,
            ],
            [
                "id" => 3224009,
                "first_name" => "Dario, Jr.",
                "last_name" => "Miñoza",
                "email" => "dario.minoza@ustp.edu.ph",
                "password" => "siimsDarioMinoza",
                "program_id" => 8,
            ],
            [
                "id" => 3101267,
                "first_name" => "John Benedict",
                "last_name" => "Bernardo",
                "email" => "jbl.bernardo@ustp.edu.ph",
                "password" => "siimsJblBernardo",
                "program_id" => 8,
            ],
            [
                "id" => 3224008,
                "first_name" => "Geraldine",
                "last_name" => "Blanco",
                "email" => "geraldine.blanco@ustp.edu.ph",
                "password" => "siimsGeraldineBlanco",
                "program_id" => 8,
            ],
        ];
    }
}
