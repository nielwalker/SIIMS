<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PredefinedCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Support Zebra (User)
        $supportZebraUser = User::create([
            "id" => 861537,
            "first_name" => "Shane Clear",
            "middle_name" => "",
            "last_name" => "Cano-og",
            "email" => "recruitement@supportzebra.com",
            "email_verified_at" => now(),
            "password" => "password",
            "gender" => 'Female',
            "phone_number" => "09177007656",
            "street" => 'Claro M. Recto Ave',
            "barangay" => "Barangay 26 G/F",
            "city_municipality" => 'Cagayan de Oro City',
            "province" => 'Misamis Oriental',
            "postal_code" => "9000"
        ]);

        // Create USER ROLE (Company)
        UserRole::create([
            'user_id' => $supportZebraUser->id,
            'role_id' => 4,
        ]);

        // Support Zebra (Company)
         Company::create([
            "id" => $supportZebraUser->id,
            "user_id" => $supportZebraUser->id,
            "name" => "SupportZebra",
            "website_url" => "www.supportzebra.com"
        ]);
    }
}
