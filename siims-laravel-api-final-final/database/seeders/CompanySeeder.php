<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CompanySeeder extends BaseSeeder
{

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $faker;

    /**
     * Faker Class
     */
    public function __construct()
    {
        // Initialize the Faker
        $this->faker = Faker::create();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the current year
        $currentYear = date('Y');

        // Get predefined companies, dedupe case-insensitively to avoid unique(name) collisions
        $companies = collect($this->getPredefinedCompanies())
            ->unique(fn ($c) => mb_strtolower($c['name']))
            ->values()
            ->all();

        // Create a user, role, and company only if the company name does not already exist
        foreach ($companies as $company) {
            $existing = Company::whereRaw('LOWER(name) = ?', [mb_strtolower($company['name'])])->first();
            if ($existing) {
                // Company already exists by name; skip to keep the seeder idempotent
                continue;
            }

            $userData = [
                'id' => $this->generateCompanyId($currentYear),
                'first_name' => $this->faker->firstName,
                'middle_name' => $this->faker->optional()->lastName,
                'last_name' => $this->faker->lastName,
                'email' => $this->faker->unique()->safeEmail,
                'email_verified_at' => now(),
                'password' => bcrypt('sample_password'),
                'gender' => $this->faker->randomElement(['male', 'female', 'other']),
                'phone_number' => $this->faker->phoneNumber,
                'street' => $this->faker->streetName,
                'barangay' => $this->faker->word,
                'city_municipality' => $this->faker->city,
                'province' => $this->faker->state,
                'postal_code' => $this->faker->postcode,
            ];

            $user = User::create($userData);

            UserRole::create([
                'role_id' => 4, // Company
                'user_id' => $user->id,
            ]);

            // Use firstOrCreate to guard against race conditions and ensure idempotency
            Company::firstOrCreate(
                ['name' => $company['name']],
                [
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'website_url' => $company['website_url'],
                ]
            );
        }
    }

    /**
     * Generate an 11-character company ID.
     *
     * @param string $currentYear
     * @return string
     */
    private function generateCompanyId(string $currentYear): string
    {
        // Generate a random 7-digit number
        $randomDigits = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);

        // Combine the year and random digits
        return $currentYear . $randomDigits;
    }

    /**
     * Summary of getPredefinedCompanies: A private function that gets the Pre-defiend Companies.
     * @return array
     */
    private function getPredefinedCompanies(): array
    {

        $companies = [
            // A
            [
                "name" => "AERONICS INCORPORATED AI",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Allied Services Multi Purpose Cooperative (ASMPC)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Altair Engineering Inc.",
                "website_url" => $this->faker->url,
            ],

            [
                "name" => "Asia Brewery",
                "website_url" => $this->faker->url,
            ],

            [
                "name" => "Azpired Inc.",
                "website_url" => $this->faker->url,
            ],

            [
                "name" => "ABOITIZ POWER CORPORATION",
                "website_url" => $this->faker->url,
            ],

            [
                "name" => "AYALA LAND MALLS INC.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "ALLIANZ PNB",
                "website_url" => $this->faker->url,
            ],
            // B
            [
                "name" => "Bureau of Fisheries and Aquatic Resources",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "BUREAU OF FIRE PROTECTION",
                "website_url" => $this->faker->url,
            ],
            // C
            [
                "name" => "Carboncycle Processing Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Casinglot National High School",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Cebu Mitsumi Inc. - Minebea Mitsumi, Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "CK Children's Publishing and Printing",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Cleversoft, Inc.	",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Climbs Life and General Insurance Cooperative",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Columbia Computer Center",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Commission on Higher Education (CHED) - Region X",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Commission on Audit (COA)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Concentrix CVG Philippines, Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "C-One Trucks and Equipment",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "CENTRAL JUAN IT SOLUTIONS",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "CEREBRO DIAGNOSTIC SYSTEM",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "COMMISSION ON AUDIT",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "CITY GOVERNMENT OF CAGAYAN DE ORO",
                "website_url" => $this->faker->url,
            ],
            // D
            [
                "name" => "DENR - Mines and Geoscience Bureau Region 10 (DENR MGB)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Department of Agrarian Reform Regional 10",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Department of Education (DepEd)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Department of the Interior and Local Government Region 10",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "DENR REGION 10",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "DEPARTMENT OF AGRARIAN REFORM REGIONAL OFFICE NO.1O",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "DEPARTMENT OF LABOR AND EMPLOYMENT - X CAGAYAN DE ORO CITY FIELD OFFICE ( DOLE X CDOFO)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "DEPARTMENT OF PUBLIC WORKS AND HIGHWAYS - BUKIDNON 3ND DISTRICT ENGINEERING OFFICE",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "DEPARTMENT OF PUBLIC WORKS AND HIGHWAYS - MISAMIS ORIENTAL FIRST DISTRICT ENGINEERING OFFICE",
                "website_url" => $this->faker->url,
            ],
            // E
            [
                "name" => "Evergreen Shipping Agency Philippines Corporation",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "EMBERNXT IT SOLUTIONS",
                "website_url" => $this->faker->url,
            ],
            // F
            [
                "name" => "Fast Autoworld Phil. Corp. (MITSUBISHI MOTORS-CORP CDO)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "F.A.S.T. LABORATORIES",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "FDC Misamis Power Corporation",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Fligno Software Philippines Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "FICCO COMMUNITY OUTREACH FOUNDATION INC.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "FUA SURVEYING SERVICES",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "FLIGNO SOFTWARE PHILIPPINES, INC.",
                "website_url" => $this->faker->url,
            ],
            // H
            [
                "name" => "HC Consumer Finance Philippines Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Hyperstacks, Inc.",
                "website_url" => $this->faker->url,
            ],
            // I
            [
                "name" => "Infinitecare Technology Solutions Inc.",
                "website_url" => $this->faker->url,
            ],
            // J
            [
                "name" => "Jacobi Carbon Philippines Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Jonrich Enterprises, Inc.",
                "website_url" => $this->faker->url,
            ],
            // K
            [
                "name" => "KYOGOJO ENGINEERING AND WATER SERVIVE COOPERATIVE",
                "website_url" => $this->faker->url,
            ],
            // L
            [
                "name" => "Local Government Unit of Villanueva",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "LGU MANOLO FORTICH",
                "website_url" => $this->faker->url,
            ],
            // M
            [
                "name" => "MGM MOTORS MINDANAO INC. (MAZDA CAGAYAN DE ORO)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Millennium Cars Mindanao, Inc. (Ford CDO)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "MISAMIS ORIENTAL -1 RURAL ELECTRIC SERVICE COOPERATIVE, INC",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Molugan National High School",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Monster Kitchen Incorporated (MKI)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Municipal Government of Libona",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "MAGNETEK ENGINEERING",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "MAKATI DEVELOPMENT CORPORATION",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "MOCA EVENTS",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "MELDCX PHILIPPINES INC.",
                "website_url" => $this->faker->url,
            ],
            // N
            [
                "name" => "Northstar Technologies Industrial Corporation 2023",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "NATIONAL IRRIGATION ADMINISTRATION",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "NEW HOPE PRECISION AGRICULTURE CORPORATION",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "NORTH-MIN AUTO DEALERSHIP, INC.",
                "website_url" => $this->faker->url,
            ],
            // O
            [
                "name" => "Oro Asian Automotive Center Corporation",
                "website_url" => $this->faker->url,
            ],
            // P
            [
                "name" => "Princetech Corporation",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Professional Regulation Commission Region 10",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Provincial Environment and Natural Resources Office - Misamis Oriental",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "P AND P ENGINEERING SERVICES",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "PRONATURAL-FEED-CORP",
                "website_url" => $this->faker->url,
            ],
            // Q
            [
                "name" => "QUIBLAT SURVEYING SERVICES",
                "website_url" => $this->faker->url,
            ],
            // R
            [
                "name" => "Red Lemon Digital Media",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Red Ribbon Bakeshop, Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "RSPOT Solutions, Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "REFUGE CONSTRUCTION SERVICES",
                "website_url" => $this->faker->url,
            ],
            // S
            [
                "name" => "Seers Property Management Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Semiconductor and Electronics Industries in the Philippines Foundation, Inc. (SEIPI)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Skunk Works OPC",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Social Security System (SSS)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Syntactics Inc (2015)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Syntactics Inc (2017)",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "Syntactics Inc. (2022)",
                "website_url" => $this->faker->url,
            ],
            // T
            [
                "name" => "Tagoloan National High School",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "ThinkLogic Media Group",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "THINKLOGIC MEDIA GROUP",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "THINKGLOBAL CAREER CONSULTANCY SERVICES, INC.",
                "website_url" => $this->faker->url,
            ],
            // U
            [
                "name" => "ULTRACRAFT ADVERTISING CORPORATION",
                "website_url" => $this->faker->url,
            ],
            // V
            [
                "name" => "Vifel Ice Plant and Cold Storage Inc.",
                "website_url" => $this->faker->url,
            ],
            // W
            [
                "name" => "Wela School System",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "WEE ENG CONSTRUCTION, INC",
                "website_url" => $this->faker->url,
            ],
            // X
            [
                "name" => "XYONI CORPORATION",
                "website_url" => $this->faker->url,
            ],
            // Y

            [
                "name" => "Yahshua Outsourcing Worldwide, Inc.",
                "website_url" => $this->faker->url,
            ],
            [
                "name" => "YAHSHUA OUTSOURCING WORLDWIDE, INC. the Service arm of ABBA Initiative OPC",
                "website_url" => $this->faker->url,
            ],
        ];

        // Return Pre-defined companies
        return $companies;
    }
}
