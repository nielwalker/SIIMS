<?php

namespace Database\Seeders;

use App\Models\College;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CollegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

       

        // USTP CDO Colleges
        College::insert([
            [   
                "id" => 1,
                "campus_id" => 1,
                "name" => "College of Engineering and Architecture"
            ],
            // ! Transfered to the User Seeder
            /* [   
                "id" => 2,
                "campus_id" => 1,
                "name" => "College of Information Technology and Computing",
            ], */
            [   
                "id" => 3,
                "campus_id" => 1,
                "name" => "College of Science and Mathematics"
            ],
            [   
                "id" => 4,
                "campus_id" => 1,
                "name" => "College of Science and Technology Education"
            ],
            [   
                "id" => 5,
                "campus_id" => 1,
                "name" => "College of Technology"
            ],
            [   
                "id" => 6,
                "campus_id" => 1,
                "name" => "College of Medicine"
            ],
            [   
                "id" => 7,
                "campus_id" => 1,
                "name" => "Senior High School"
            ],
        ]);
    }
}
