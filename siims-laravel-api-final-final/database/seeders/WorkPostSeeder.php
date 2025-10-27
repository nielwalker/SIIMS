<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\WorkPost;
use App\Models\WorkType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class WorkPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create faker instance
        $faker = Faker::create();
        
        // Fetch all offices that have a company_id
        $offices = Office::whereNotNull('company_id')->get();

        // Fetch all work types
        $workTypes = WorkType::all();

        foreach ($offices as $office) {
            // Determine a random number of job posts for the current office (1 to 5)
            $numPosts = rand(1, 2);

            for ($i = 0; $i < $numPosts; $i++) {
                // Generate start_date within the next 30 days
                $startDate = Carbon::now()->addDays(rand(1, 30));

                // Generate end_date within 7 days after the start_date
                $endDate = $startDate->copy()->addDays(rand(0, 7));

                WorkPost::create([
                    'office_id' => $office->id,
                    'work_type_id' => $workTypes->random()->id, // Randomly select a work type
                    //'title' => $faker->jobTitle(),
                    'title' => 'Intern',
                    'responsibilities' => $faker->jobTitle(),
                    'qualifications' => $faker->paragraph(),
                    'max_applicants' => rand(1, 10), // Random max applicants between 1 and 10
                    'start_date' => $startDate, // Set generated start date
                    'end_date' => $endDate, // Set generated end date
                    'work_duration' => rand(1, 6) . ' months', // Random work duration between 1 to 6 months
                ]);
            }
        }
    }
}
