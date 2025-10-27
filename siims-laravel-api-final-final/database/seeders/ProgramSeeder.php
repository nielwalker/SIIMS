<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Campus: USTP CDO
         * College: College of Engineering and Architecture
         */
        Program::insert([
            [
                "college_id" => 1,
                "name" => "Bachelor of Science in Architecture",
                
            ],
            [
                "college_id" => 1,
                "name" => "Bachelor of Science in Civil Engineering",
                
            ],
            [
                "college_id" => 1,
                "name" => "Bachelor of Science in Mechanical Engineering",
              
            ],
            [
                "college_id" => 1,
                "name" => "Bachelor of Science in Computer Engineering",
              
            ],
            [
                "college_id" => 1,
                "name" => "Bachelor of Science in Geodetic Engineering",
               
            ],
            [
                "college_id" => 1,
                "name" => "Bachelor of Science in Electrical Engineering",
                
            ],
            [
                "college_id" => 1,
                "name" => "Bachelor of Science in Electronics Engineering",
             
            ],
        ]);
        
        /**
         * Campus: USTP CDO
         * College: College of Information Technology and Computing
         */
        Program::insert([

            // ! This array is transfered to the UserSeeder
            /* [
                "college_id" => 2,
                "name"=> "Bachelor of Science in Information Technology",
                "max_internships" => 1,
            
            ], */
            /* [
                "college_id" => 2,
                "name"=> "Bachelor of Science in Technology Communication Management",
                "max_internships" => 2,
              
            ], */
            [
                "college_id" => 2,
                "name"=> "Bachelor of Science in Data Science",
                "max_internships" => 2,
             
            ],
            [
                "college_id" => 2,
                "name"=> "Bachelor of Science in Computer Science",
                "max_internships" => 1,
               
            ],
        ]);

        /**
         * Campus: USTP CDO
         * College: College of Science and Mathematics
         */
        Program::insert([
            [
                "college_id" => 3,
                "name" => "Bachelor of Science in Applied Mathemathics",
             
            ],
            [
                "college_id" => 3,
              
                "name" => "Bachelor of Science in Applied Mathemathics"
            ],
            [
                "college_id" => 3,
             
                "name" => "Bachelor of Science in Applied Physics"
            ],
            [
                "college_id" => 3,
                
                "name" => "Bachelor of Science in Chemistry"
            ],
            [
                "college_id" => 3,
               
                "name" => "Bachelor of Science in Environmental Science"
            ],
            [
                "college_id" => 3,
               
                "name" => "Bachelor of Science in Food Technology"
            ],
        ]);

        /**
         * Campus:: USTP CDO
         * College: College of Science and Technology Education
         */
        Program::insert([
            [
                "college_id" => 4,
              
                "name" => "Bachelor in Secondary Education Major in Science"
            ],
            [
                "college_id" => 4,
               
                "name" => "Bachelor in Secondary Education Major in Mathematics"
            ],
            [
                "college_id" => 4,
               
                "name" => "Bachelor in Technology and Livelihood Education"
            ],
            [
                "college_id" => 4,
                
                "name" => "Bachelor in Technical Vocational Teacher Education"
            ],
        ]);

        /**
         * Campus: USTP CDO 
         * College: College of Technology
         */
        Program::insert([
            [
                "college_id" => 5,
               
                "name" => "Bachelor of Science in Electronics Technology"
            ],
            [
                "college_id" => 5,
               
                "name" => "Bachelor of Science in Autotronics"
            ],
            [
                "college_id" => 5,
              
                "name" => "Bachelor of Science in Energy Systems and Management"
            ],
            [
                "college_id" => 5,
               
                "name" => "Bachelor of Science in Electro-Mechanical Technology"
            ],
            [
                "college_id" => 5,
              
                "name" => "Bachelor of Science in Manufacturing Engineering Technology"
            ],
        ]);

        /**
         * Campus: USTP CDO
         * College: College of Medicine
         */
        Program::insert([
            [
                
            ]
        ]);
    }
}
