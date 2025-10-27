<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeanCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class DeanCompanyController extends Controller
{

    /**
     * Summary of getAllOffices: Get company offices by company_id
     * @param string $company_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllOffices(String $company_id) {

        // Find and check company
        $this->findCompany($company_id);

        // Find offices by company_id
        $offices = Company::with(['offices', 'user'])->has('offices')->find($company_id);

        // Return offices with company
        return response()->json($offices->offices, 200);

    }

     /**
     * Summary of findCompany: Find a company by company_id
     * @param string $company_id
     * @return Company|mixed|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function findCompany(String $company_id) {
        
        // Find company
        $company_user = Company::with('user')->has('user')->find($company_id);
        // Check if company exist
        if(!$company_user) {
            return response()->json(['message' => 'Company not found.'], 404);
        }
        
        // Return company
        return $company_user;
    }

    // Company Table Format
    public function table()
    {
        // Get Companies
        $companies = Company::has('user')->with('user')->withCount('offices')->get();
        if (!$companies) {
            return response()->json(['message' => 'Companies not found.'], 404);
        }

        // Transform companies
        $transformedCompanies = $this->mapTransform($companies);

        // Return
        return $transformedCompanies;
    }

    // Mapping Company
    public function mapTransform($companies)
    {
        $transformedCompanies = $companies->map(function ($company) {
            return $this->transform($company);
        });

        // Return 
        return $transformedCompanies;
    }

    // Transform Company
    public function transform($company) {
        return [
            "id" => $company['id'],
            "company_name" => $company['name'],
            "total_offices" => $company['offices_count'],
            "website_url" => $company['website_url'],
            "email" => $company['user']['email'],
            "phone_number" => $company['user']['phone_number'],
            "street" => $company['user']['street'],
            "barangay" => $company['user']['barangay'],
            "city_municipality" => $company['user']['city_municipality'],
            "province" => $company['user']['province'],
            "postal_code" => $company['user']['postal_code'],
            "created_at" => $company['user']['created_at'],
            "updated_at" => $company['user']['updated_at'],
        ];
    }

    // Delete a Company
    public function destroy(String $company_id) {
        // Find the company
        $company = Company::find($company_id);
        // Check if company exist
        if(!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        //Get User
        $user = User::find($company['id']);

        // Soft Deletes Company
         if ($company->delete()) {
            // Soft Deletes User
            $user->delete();
        }

        // Return
        return response()->json(['message' => 'Company deleted.'], 200);
    }

    // Store a Company
    public function store(DeanCompanyRequest $request) {
        // Get validated Credentials
        $validatedCredentials = $request->validated();
       
        // Fill User Credentials
        $userCredentials = [
            'id' => $validatedCredentials['id'],
            'first_name' => $validatedCredentials['first_name'],
            'middle_name' => $validatedCredentials['middle_name'],
            'last_name' => $validatedCredentials['last_name'],
            'email' => $validatedCredentials['email'],
            'password' => $validatedCredentials['password'],
            'gender' => $validatedCredentials['gender'],
            'phone_number' => $validatedCredentials['phone_number'],
            'street' => $validatedCredentials['street'],
            'barangay' => $validatedCredentials['barangay'],
            'city_municipality' => $validatedCredentials['city_municipality'],
            'province' => $validatedCredentials['province'],
            'postal_code' => $validatedCredentials['postal_code'],
        ];

        // Insert User Query
        $user = User::create($userCredentials);

        // Check if the user exist
        if (!$user) {
            return response()->json(['User could be created'], 400);
        }

        // Insert Company Role
        UserRole::create([
           "user_id" => $user['id'],
           "role_id" => 4,
        ]);

        // Insert Company Query
        Company::create([
            "id" => $user['id'],
            "user_id" => $user['id'],
            "name" => $validatedCredentials['company_name'],
            "website_url" => $validatedCredentials['website_url'],
        ]);
        
        // Get Companies
        $companies = $this->table();

        // Return Success Full Create
        return response()->json([
            'message' => 'A Company is created.',
            'data' => $companies,
        ], 201);
    }

    // Get All Companies
    public function index() {
        // Get all programs
        $companies = $this->table();

        // Return
        return response()->json($companies, 200);
    }

    // Get a Company
    public function show(String $company_id) {
        // Find Company
        $company = $this->find($company_id);

        // Transform Company
        $company = $this->transform($company);

        // Return
        return response()->json($company, 200);
    }

    // Find Company
    public function find(String $company_id) {

        // Find Company
        $company = Company::has('user')->with(['user'])->find($company_id);
        // Check Company if Exist
        if(!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Return Company
        return $company;
    }
}
