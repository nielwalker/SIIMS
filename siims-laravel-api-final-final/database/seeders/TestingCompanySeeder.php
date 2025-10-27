<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestingCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch companies
        $companies = $this->getCompanies();

        /**
         * - Loop each company
         * - Create new user
         * - Create new role
         * - Create new company
         */

        foreach ($companies as $company) {

            // Create new user
            $user = User::create([
                'id' => $company['id'],
                'first_name' => $company['first_name'],
                'last_name' => $company['last_name'],
                'email' => $company['email'],
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'gender' => ucfirst($company['gender']),
                'phone_number' => $company['phone_number'],
                'street' => $company['street'],
                'barangay' => $company['barangay'],
                'city_municipality' => $company['city_municipality'],
                'province' => $company['province'],
                'postal_code' => $company['postal_code'],
            ]);

            // Create new role
            UserRole::create(
                [
                    "user_id" => $user->id,
                    "role_id" => 4, // Company
                ],

            );

            // Create new company
            Company::create($company['company']);
        }
    }

    /**
     * Summary of getCompanies: A private function that gets all list of companies
     * @return array
     */
    private function getCompanies()
    {
        // Array of companies
        $companies = [
            [
                'id' => 1001,
                'first_name' => 'John',
                'middle_name' => 'Albert',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => bcrypt('securePassword123'),
                'gender' => 'male',
                'phone_number' => '09123456789',
                'street' => '123 Main St',
                'barangay' => 'Barangay Uno',
                'city_municipality' => 'Cagayan de Oro',
                'province' => 'Misamis Oriental',
                'postal_code' => '9000',
                'company' => [
                    'id' => 1001,
                    'user_id' => 1001,
                    'name' => 'Tech Solutions Inc.',
                    'website_url' => 'https://techsolutions.com',
                ],
            ],
            [
                'id' => 1002,
                'first_name' => 'Jane',
                'middle_name' => 'Marie',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'password' => bcrypt('JaneSecure456'),
                'gender' => 'female',
                'phone_number' => '09187654321',
                'street' => '456 Elm Street',
                'barangay' => 'Barangay Dos',
                'city_municipality' => 'Iligan City',
                'province' => 'Lanao del Norte',
                'postal_code' => '9200',
                'company' => [
                    'id' => 1002,
                    'user_id' => 1002,
                    'name' => 'Innovatech Corp.',
                    'website_url' => 'https://innovatech.com',
                ],
            ],
            [
                'id' => 1003,
                'first_name' => 'Michael',
                'middle_name' => 'Joseph',
                'last_name' => 'Brown',
                'email' => 'michael.brown@example.com',
                'password' => bcrypt('MikeyPass789'),
                'gender' => 'male',
                'phone_number' => '09212345678',
                'street' => '789 Maple Avenue',
                'barangay' => 'Barangay Tres',
                'city_municipality' => 'Butuan City',
                'province' => 'Agusan del Norte',
                'postal_code' => '8600',
                'company' => [
                    'id' => 1003,
                    'user_id' => 1003,
                    'name' => 'GlobalTech Solutions',
                    'website_url' => 'https://globaltech.com',
                ],
            ],
        ];

        // Return 
        return $companies;
    }
}
