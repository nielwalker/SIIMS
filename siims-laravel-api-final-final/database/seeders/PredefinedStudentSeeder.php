<?php

namespace Database\Seeders;

use App\Models\Education;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use App\Models\WorkExperience;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PredefinedStudentSeeder extends SectionSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get students
        $students = $this->getStudents();

        /**
         * - Loop each student
         * - Create a user
         * - Create user role
         * - Find Program by ID
         * - Create a student
         */
        foreach ($students as $student) {

            // Create user
            $user = User::create([
                "id" => $student['id'],
                "first_name" => $student['first_name'],
                "middle_name" => $student['middle_name'],
                "last_name" => $student['last_name'],
                "email" => $student['email'],


                "phone_number" => $student['phone_number'],
                "password" => bcrypt($student['password']),
                "gender" => $student['gender'],
                "street" => $student['street'],
                "barangay" => $student['barangay'],
                "city_municipality" => $student['city_municipality'],
                "province" => $student['province'],
                "postal_code" => $student['postal_code'],
                

            ]);

            // Create user role 
            UserRole::create([
                "user_id" => $user->id,
                "role_id" => 7,
            ]);

            // Create Student
            Student::create([
                'id' => $user->id,
                'user_id' => $user->id,
                'program_id' => 8, // BSIT,
                'coordinator_id' => $student['student']['coordinator_id'],
                'age' => $student['student']['age'],
                'date_of_birth' => $student['student']['date_of_birth'],
                'student_status_id' => $student['student']['student_status_id'],
                'about_me' => $student['student']['about_me'],
                'section_id' => $this->UUID_1,
            ]);

            // FOR STUDENT ID 
            if ($user['id'] === 2025601502) {

                WorkExperience::insert([
                    [
                        'student_id' => $user['id'],
                        'job_position' => 'Software Developer Intern',
                        'company_name' => 'Tech Solutions Inc.',
                        'full_address' => '123 Tech Avenue, RiverCity, WestProvince, 45678',
                        'start_date' => '2023-06-01', // Start of the internship
                        'end_date' => '2023-08-31',   // End of the internship
                    ],
                    [
                        'student_id' => $user['id'],
                        'job_position' => 'IT Support Specialist',
                        'company_name' => 'Innovate IT Services',
                        'full_address' => '789 Innovation Drive, RiverCity, WestProvince, 45678',
                        'start_date' => '2022-09-15',
                        'end_date' => '2023-05-30',
                    ],
                ]);

                // Insert Education
                Education::insert([
                    [
                        'student_id' => $user['id'],
                        'school_name' => 'RiverCity High School',
                        'full_address' => '456 Education Lane, RiverCity, WestProvince, 45678',
                        'start_date' => '2016-06-01',
                        'end_date' => '2020-03-31',
                    ],
                    [
                        'student_id' => $user['id'],
                        'school_name' => 'WestProvince State University',
                        'full_address' => '321 University Road, RiverCity, WestProvince, 45678',
                        'start_date' => '2020-08-01',
                        'end_date' => '2024-05-31', // Expected graduation date
                    ],
                ]);
            }
        }
    }

    /**
     * Summary of getStudents: A private function that gets all the list of students.
     * @return array
     */
    private function getStudents()
    {

        // Array of students
        $students = [
            [
                "id" => 2025601502,
                "first_name" => "Michael",
                "middle_name" => "Angelo",
                "last_name" => "Santos",
                'email' => 'michaelangelo.santos@example.com',
                'password' => "password",
                'gender' => 'male',
                'phone_number' => '123-456-7890',
                'street' => 'Pine Avenue',
                'barangay' => 'BarangayWest',
                'city_municipality' => 'RiverCity',
                'province' => 'WestProvince',
                'postal_code' => '45678',

                // Student Info
                'student' => [
                    'age' => 20, // Calculated as of current year
                    'coordinator_id' => $this->COORDINATOR_ID,
                    'date_of_birth' => '2004-01-15', // Generated DOB
                    'student_status_id' => 1, // Not Yet Enrolled
                    'about_me' => "Hello, I am Michael Angelo Santos. I am a 20-year-old student from RiverCity, WestProvince. I am passionate about technology, specifically programming and software development. I enjoy learning new skills and improving my knowledge in computer science. I am looking forward to making meaningful contributions to the tech community and pursuing a successful career in the field."
                ],
            ]
        ];

        // Return
        return $students;
    }
}
