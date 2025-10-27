<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\College;
use App\Models\Company;
use App\Models\Coordinator;
use App\Models\Office;
use App\Models\Program;
use App\Models\Skill;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends BaseSeeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ! Type Seeder Insertions 
        $this->call([
            RoleSeeder::class,
            WorkTypeSeeder::class,
            OfficeTypeSeeder::class,
            DocumentStepSeeder::class,
            DocumentTypeSeeder::class,
            // SkillSeeder::class,
            TimeRecordStatusSeeder::class,
            ApplicationStatusSeeder::class,
            StudentStatusSeeder::class,
            StatusSeeder::class,
            EndorsementLetterRequestStatusSeeder::class,
            ReportTypeSeeder::class,
            DocumentStatusSeeder::class,
        ]);

        // ! User ID
        // ! NOTE: THIS ID's ARE TRANSFERRED TO THE SEEDER CLASS (PARENT)
        # Users provided IDs:
        /* $admin_id = 2021301502;
        $company_id = 2023301502;
        $student_id = 2024301502;
        $dean_id = 2020301502;
        $chairperson_id = 2019301502; */

        /**
         * This is where you add user if you want to test the backend
         * Note: After you add new user make sure you add their user roles below in this variable $users. You can see a conditional statement there.
         */
        $users = User::insert([
            // This is a user that has a role of admin
            [
                'id' => $this->ADMIN_ID,
                'first_name' => 'John Mercury',
                'middle_name' => 'Jupiter',
                'last_name' => 'Sonar',
                'email' => 'sanchezhanszin123@gmail.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->ADMIN_PASSWORD),
                'gender' => 'male',
                'phone_number' => '321-654-9870',
                'street' => 'Elm Street',
                'barangay' => 'BarangayEast',
                'city_municipality' => 'NewTown',
                'province' => 'EastProvince',
                'postal_code' => '78901',
            ],
            // This is a user that has a role of company
            [
                'id' => $this->COMPANY_ID,
                'first_name' => '',
                'middle_name' => '',
                'last_name' => '',
                'email' => 'businessSanchez@email.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->COMPANY_PASSWORD),
                'gender' => 'male',
                'phone_number' => '123-456-789',
                'street' => 'Claro M. Recto Avenue',
                'barangay' => 'Lapasan',
                'city_municipality' => 'Cagayan de Oro City',
                'province' => 'Misamis Oriental',
                'postal_code' => '9000',

            ],

            // This is a user that has role of student
            [
                'id' => $this->STUDENT_ID,
                'first_name' => 'Emily',
                'middle_name' => 'Joan',
                'last_name' => 'Rodriguez',
                'email' => 'rodriguez@email.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->STUDENT_PASSWORD),
                'gender' => 'female',
                'phone_number' => '987-654-3210',
                'street' => 'Main St',
                'barangay' => 'BarangayCentral',
                'city_municipality' => 'MetroCity',
                'province' => 'CentralProvince',
                'postal_code' => '45678',
            ],
            // ! NOTE: If you want to add a dean make sure you assign him also to a college (Check below)
            // This is a user that has role of dean
            [
                'id' => $this->DEAN_ID,
                'first_name' => 'Junar',
                'middle_name' => 'A.',
                'last_name' => 'Landicho',
                'email' => 'smith@email.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->DEAN_PASSWORD),
                'gender' => 'male',
                'phone_number' => '123-456-7890',
                'street' => 'Second St',
                'barangay' => 'BarangayEast',
                'city_municipality' => 'UrbanCity',
                'province' => 'EasternProvince',
                'postal_code' => '12345',
            ],
            // ! NOTE: If you want to add a chairperson make sure you assign him also to a program (Check below)
            // This is a user that has role of chairperson
            [
                'id' => $this->CHAIRPERSON_ID,
                'first_name' => 'David',
                'middle_name' => 'Andrew',
                'last_name' => 'Johnson',
                'email' => 'david.johnson@email.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->CHAIRPERSON_PASSWORD),
                'gender' => 'male',
                'phone_number' => '987-654-3210',
                'street' => 'Third Avenue',
                'barangay' => 'BarangayWest',
                'city_municipality' => 'MetroTown',
                'province' => 'NorthernProvince',
                'postal_code' => '67890',
            ],
            // ! NOTE: If you want to add a chairperson make sure you assign him also to a program (Check below)
            // This is a user that has role of chairperson
            /* [
                'id' => $this->CHAIRPERSON_ID_2,
                'first_name' => 'Jay Noel',
                'middle_name' => '',
                'last_name' => 'Rojo',
                'email' => 'rojo.ustp@email.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->CHAIRPERSON_PASSWORD_2),
                'gender' => 'male',
                'phone_number' => '987-654-3210',
                'street' => '',
                'barangay' => '',
                'city_municipality' => ' Cagayan de Oro City',
                'province' => '',
                'postal_code' => '9000',
            ], */
            // ! NOTE: If you want to add a supervisor make sure you assign him also to a company (Check below)
            // This is a user that has role of supervisor
            [
                'id' => $this->SUPERVISOR_ID,
                'first_name' => 'Felicia',
                'middle_name' => 'Matthew',
                'last_name' => 'Fritz',
                'email' => 'moniquejones@yahoo.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->SUPERVISOR_PASSWORD),
                'gender' => 'male',
                'phone_number' => '297-107-7572',
                'street' => 'Drake Radial',
                'barangay' => 'BarangayCountry',
                'city_municipality' => 'Aaronside',
                'province' => 'Washington',
                'postal_code' => '77651',
            ],
            // ! NOTE: If you want to add a OSA make sure you assign him also to a company (Check below)
            // This is a user that has role of OSA
            [
                'id' => $this->OSA_ID,
                'first_name' => 'Olivia',
                'middle_name' => 'Marie',
                'last_name' => 'Hughes',
                'email' => 'oliviahughes@email.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->OSA_PASSWORD),
                'gender' => 'female',
                'phone_number' => '805-232-9473',
                'street' => 'Pinewood Avenue',
                'barangay' => 'BarangayLuzon',
                'city_municipality' => 'Riverside',
                'province' => 'California',
                'postal_code' => '92507',
            ],
            // ! NOTE: If you want to add a coordinator make sure you assign him also to a company (Check below)
            // This is a user that has role of coordinator
            [
                'id' => $this->COORDINATOR_ID,
                'first_name' => 'Ethan',
                'middle_name' => 'James',
                'last_name' => 'Martinez',
                'email' => 'ethanmartinez@email.com',
                'email_verified_at' => now(),
                'password' => bcrypt($this->COORDINATOR_PASSWORD),
                'gender' => 'male',
                'phone_number' => '415-658-7392',
                'street' => 'Sunset Boulevard',
                'barangay' => 'BarangaySilangan',
                'city_municipality' => 'San Francisco',
                'province' => 'California',
                'postal_code' => '94122',
            ],

        ]);

        // Check if users are created 
        if ($users) {
            // Insert user roles data
            UserRole::insert([
                # Admin
                [
                    'user_id' => $this->ADMIN_ID,
                    'role_id' => 1,
                ],
                # Company
                [
                    'user_id' => $this->COMPANY_ID,
                    'role_id' => 4,
                ],
                # Student
                [
                    'user_id' => $this->STUDENT_ID,
                    'role_id' => 7,
                ],
                # Dean
                [
                    'user_id' => $this->DEAN_ID,
                    'role_id' => 5,
                ],
                # Osa
                [
                    'user_id' => $this->OSA_ID,
                    'role_id' => 6,
                ],
                # Chairperson
                [
                    'user_id' => $this->CHAIRPERSON_ID,
                    'role_id' => 2,
                ],
                # Chairperson
                /* [
                    'user_id' => $this->CHAIRPERSON_ID_2,
                    'role_id' => 2,
                ], */
                # Supervisor
                [
                    'user_id' => $this->SUPERVISOR_ID,
                    'role_id' => 8,
                ],
                # Coordinator
                [
                    'user_id' => $this->COORDINATOR_ID,
                    'role_id' => 3,
                ]
            ]);
        }


        // ! Manual Inserts
        // * Company Seeder Manual Insert
        Company::create([
            "id" => $this->COMPANY_ID,
            "user_id" => $this->COMPANY_ID,
            "name" => "University of Science and Technology of Southern Philippines (USTP) in Cagayan de Oro",
            "website_url" => "https://www.ustp.edu.ph/",
        ]);

        // * Campus Seeder Manual Insert
        Campus::insert([
            [
                "id" => 1,
                "name" => "USTP CAGAYAN DE ORO"
            ],
        ]);
        

        // * College Seeder Manual Insert
        // USTP CDO Colleges
        College::insert([
            [
                "id" => 2,
                "campus_id" => 1,
                "name" => "College of Information Technology and Computing",
                "dean_id" => $this->DEAN_ID
            ],
        ]);
        

        // * Supervisor Seeder Manual Insert
        Supervisor::insert([
            'id' => $this->SUPERVISOR_ID,
            'user_id' => $this->SUPERVISOR_ID,
            'company_id' => $this->COMPANY_ID
        ]);

        // * Office Seeder Manual Insert
        Office::insert([
            [
                "id" => 8,
                "office_type_id" => 1,
                "name" => "Department of Science in Information Technology",
                "company_id" => $this->COMPANY_ID,
                "supervisor_id" => $this->SUPERVISOR_ID,
            ],
        ]);

        // * Program Seeder Manual Update
        Program::insert([
            [
                "id" => 8,
                "college_id" => 2,
                "name" => "Bachelor of Science in Information Technology",
                "max_internships" => 1,
              
                // "chairperson_id" => $this->CHAIRPERSON_ID_2,
            ],
        ]);
         // * Program Seeder Manual Update
         Program::insert([
            [
                "id" => 9,
                "college_id" => 2,
                "name"=> "Bachelor of Science in Technology Communication Management",
                "max_internships" => 2,
              
                "chairperson_id" => $this->CHAIRPERSON_ID,
            ],
        ]);

        // * Coordinator Seeder Manual Update
        Coordinator::insert([
            [
                "id" => $this->COORDINATOR_ID,
                "user_id" => $this->COORDINATOR_ID,
                "program_id" => 8,
            ]
        ]);

        // * Student Seeder Manual Update
        Student::insert([
            [
                "id" => $this->STUDENT_ID,
                "user_id" => $this->STUDENT_ID,
                "program_id" => 8,
                'coordinator_id' => $this->COORDINATOR_ID,
                'student_status_id' => 1, // Not Yet Applied
            ]
        ]);



        $this->call([
            CampusSeeder::class,
            CollegeSeeder::class,
            UserSeeder::class,
            UserRoleSeeder::class,
            CompanySeeder::class,

            PredefinedCompanySeeder::class,
          
            OfficeSeeder::class,
            // WorkPostSeeder::class,
            ProgramSeeder::class,
            CoordinatorSeeder::class,
            SectionSeeder::class,
            StudentSeeder::class,
            TestingChamberSeeder::class,
            
            // Predefined Chamber
            PredefinedChamberSeeder::class,

            // Testing Chamber
            // TestingChamberSeeder::class,
        ]);
    }
}
