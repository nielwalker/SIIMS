<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OfficeSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Insert School Department Offices
        Office::insert([
            [
                "id" => 1,
                "office_type_id" => 1,
                "name" => "Department of Science in Architecture",
                "company_id" => $this->COMPANY_ID,
            ],
            [
                "id" => 2,
                "office_type_id" => 1,
                "company_id" => $this->COMPANY_ID,
                "name" => "Department of Science in Civil Engineering",
            ],
            [
                "id" => 3,
                "office_type_id" => 1,
                "company_id" => $this->COMPANY_ID,
                "name" => "Department of Science in Mechanical Engineering",
            ],
            [
                "id" => 4,
                "office_type_id" => 1,
                "company_id" => $this->COMPANY_ID,
                "name" => "Department of Science in Computer Engineering",
            ],
            [
                "id" => 5,
                "office_type_id" => 1,
                "company_id" => $this->COMPANY_ID,
                "name" => "Department of Science in Geodetic Engineering",
            ],
            [
                "id" => 6,
                "office_type_id" => 1,
                "company_id" => $this->COMPANY_ID,
                "name" => "Department of Science in Electrical Engineering",
            ],
            [
                "id" => 7,
                "office_type_id" => 1,
                "company_id" => $this->COMPANY_ID,
                "name" => "Department of Science in Electronics Engineering",
            ],
        ]);
        
        /**
         * Campus: USTP CDO
         * College: College of Information Technology and Computing
         */
        Office::insert([
            // ! Transfered to the Database Seeder
            /* [
                "id" => 8,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name"=> "Department of Science in Information Technology",
            ], */
            [
                "id" => 9,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name"=> "Department of Science in Technology Communication Management",
            ],
            [
                "id" => 10,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name"=> "Department of Science in Data Science",
            ],
            [
                "id" => 11,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name"=> "Department of Science in Computer Science",
            ],
        ]);

        /**
         * Campus: USTP CDO
         * College: College of Science and Mathematics
         */
        Office::insert([
            [
                "id" => 12,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Applied Mathemathics"
            ],
            [
                "id" => 13,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Applied Mathemathics"
            ],
            [
                "id" => 14,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Applied Physics"
            ],
            [
                "id" => 15,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Chemistry"
            ],
            [
                "id" => 16,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Environmental Science"
            ],
            [
                "id" => 17,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Food Technology"
            ],
        ]);

        /**
         * Campus:: USTP CDO
         * College: College of Science and Technology Education
         */
        Office::insert([
            [
                "id" => 18,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department in Secondary Education Major in Science"
            ],
            [
                "id" => 19,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department in Secondary Education Major in Mathematics"
            ],
            [
                "id" => 20,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department in Technology and Livelihood Education"
            ],
            [
                "id" => 21,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department in Technical Vocational Teacher Education"
            ],
        ]);

        /**
         * Campus: USTP CDO 
         * College: College of Technology
         */
        Office::insert([
            [
                "id" => 22,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Electronics Technology"
            ],
            [
                "id" => 23,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Autotronics"
            ],
            [
                "id" => 24,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Energy Systems and Management"
            ],
            [
                "id" => 25,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Electro-Mechanical Technology"
            ],
            [
                "id" => 26,
                "office_type_id" => 1,
                'company_id' => $this->COMPANY_ID,
                "name" => "Department of Science in Manufacturing Engineering Technology"
            ],
        ]);

        // Insert Company Office
        // Fetch all companies
        $companies = Company::all();

        foreach($companies as $company) {
            // Create offices for each company
            Office::create([
                'office_type_id' => 1, // Replace with a valid office type ID
                'company_id' => $company['user_id'],
                'name' => 'Main Office for ' . $company->name,
                'phone_number' => '+123456789',
                'street' => '123 Main St',
                'barangay' => 'Barangay 1',
                'city_municipality' => 'Sample City',
                'province' => 'Sample Province',
                'postal_code' => '12345',
            ]);

            Office::create([
                'office_type_id' => 2, // Replace with a valid office type ID
                'company_id' => $company['user_id'],
                'name' => 'Branch Office for ' . $company->name,
                'phone_number' => '+987654321',
                'street' => '456 Elm St',
                'barangay' => 'Barangay 2',
                'city_municipality' => 'Another City',
                'province' => 'Another Province',
                'postal_code' => '67890',
            ]);
        }

        
        
    }
}
