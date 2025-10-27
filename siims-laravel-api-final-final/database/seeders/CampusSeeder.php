<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Campuses Records
        Campus::insert([
            // ! Transfered to the DatabaseSeeder
            /* [
                "id" => 1,
                "name"=> "USTP CAGAYAN DE ORO"
            ], */
            [
                "id" => 2,
                "name"=> "USTP ALUBIJID"
            ],
            [
                "id" => 3,
                "name"=> "USTP CLAVERIA"
            ],
            [
                "id" => 4,
                "name"=> "USTP VILLANUEVA"
            ],
            [
                "id" => 5,
                "name"=> "USTP BALUBAL"
            ],
            [
                "id" => 6,
                "name"=> "USTP JASAAN"
            ],
            [
                "id" => 7,
                "name"=> "USTP OROQUIETA"
            ],
            [
                "id" => 8,
                "name"=> "USTP PANAON"
            ],
        ]);
    }
}
