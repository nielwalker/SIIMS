<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Imports\CompanyImport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AdminCompanyController extends Controller
{

    /* public function importCompanies(Request $request, ActionLogger $actionLogger)
{
    // Validate the request for a file
    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:csv,txt|max:10240', // Add file size limit (10MB in this case)
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Invalid file.'], 422);
    }

    // Store the uploaded file temporarily
    $filePath = $request->file('file')->store('temp');

    try {
        // Read the CSV file
        $csvFile = Storage::disk('public')->get($filePath);

        // Parse the CSV data
        $rows = array_map('str_getcsv', explode("\n", $csvFile));
        $header = array_shift($rows);

        // Process each row and insert into the database
        foreach ($rows as $row) {
            if (empty($row)) continue;

            $companyData = array_combine($header, $row);

            // Validate the data for each company
            $companyValidator = Validator::make($companyData, [
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:100',
                'gender' => 'nullable|string|max:10',
                'phone_number' => 'nullable|string|max:20',
                'street' => 'nullable|string|max:100',
                'barangay' => 'nullable|string|max:100',
                'city_municipality' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'company_name' => 'required|string|max:255',
                'website_url' => 'nullable|string|max:255',
            ]);

            if ($companyValidator->fails()) {
                // Log validation failure and continue
                $actionLogger->logAction(
                    actionType: 'Import',
                    entity: 'Company',
                    entityId: 0,
                    description: "Validation failed for company: " . implode(", ", $companyData),
                    status: 'Failed',
                    httpCode: 422,
                );
                continue;
            }

            // Create the User and Company records as described before
            // ...

            // Log the successful creation
            $actionLogger->logAction(
                actionType: 'Import',
                entity: 'Company',
                entityId: $user->id,
                description: "Created new company: " . $companyData['company_name'],
                status: 'Success',
                httpCode: 201,
            );
        }
    } catch (\Exception $e) {
        // Log the error and return response
        $actionLogger->logAction(
            actionType: 'Import',
            entity: 'Company',
            entityId: 0,
            description: "Error occurred during CSV import: " . $e->getMessage(),
            status: 'Failed',
            httpCode: 500,
        );
        return response()->json(['message' => 'An error occurred while importing companies.'], 500);
    } finally {
        // Delete the temporary file
        Storage::disk('public')->delete($filePath);
    }

    return response()->json(['message' => 'Companies imported successfully.'], 200);
} */





    // Archive Selected Companies 
    public function archiveCompanies(Request $request) {
         // Validate the request to ensure `ids` is an array of integers
         $validated = $request->validate([
            'ids' => 'required',
            'ids.*' => 'integer', // Each item in the array should be an integer
        ]);

        // Find records based on the provided IDs
        $companies = Company::whereIn('id', $validated['ids']);
        $users = User::whereIn('id', $validated['ids']);

        // Soft delete the companies records (archiving)
        if (!$companies->exists() && !$users->exists()) {
            // Return error if no records found
            return response()->json(['error' => 'No companies found with the provided IDs'], 404);
        }

        // Soft Deletes
        $companies->delete();
        $users->delete();

        // Get Companies
        $companies = $this->table();

        // Return
        return response()->json(['message' => 'Companies archived successfully', 'data' => $companies], 201);
    }

    // Archive Company
    public function archive(String $company_id) {
          // Find Company by ID
          $company = Company::find($company_id);

          // Check if the company exists
          if (!$company) {
              return response()->json(['message' => 'Company not found'], 404);
          }
  
          // Get also the user
          $user = User::find($company_id);
  
          // Delete Company
          if ($company->delete()) {
              // Delete User
              $user->delete();
          }

          // Get table
          $companies = $this->table();
  
          // Return successful delete
          return response()->json([
              'message' => 'The company has been deleted.',
              'data' => $companies,
          ], 201);
    }

    // Company Table Format
    public function table() {
        // Get Companies
        $companies = Company::has('user')->with('user')->withCount('offices')->get();
        if(!$companies) {
            return response()->json(['message' => 'Companies not found.'], 404);
        }

        // Transform companies
        $transformedCompanies = $this->mapTransform($companies);

        // Return
        return $transformedCompanies;
    }

    // List of Companies
    public function index()
    {
        // Get companies
        $companies = $this->table();

        // Return
        return response()->json($companies, 200);
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
    public function transform($company)
    {
        return [
            "id" => $company['id'],
            "website_url" => $company['website_url'],
            "company_name" => $company['name'],
            "total_offices" => $company['offices_count'],
            "first_name" => $company['user']['first_name'],
            "middle_name" => $company['user']['middle_name'],
            "last_name" => $company['user']['last_name'],
            "email" => $company['user']['email'],
            "email_verified_at" => $company['user']['email_verified_at'],
            "gender" => $company['user']['gender'],
            "phone_number" => $company['user']['phone_number'],
            "street" => $company['user']['street'],
            "barangay" => $company['user']['barangay'],
            "city_municipality" => $company['user']['city_municipality'],
            "province" => $company['user']['province'],
            "postal_code" => $company['user']['postal_code'],
            "created_at" => $company['created_at'],
            "updated_at" => $company['updated_at'],
            "deleted_at" => $company['deleted_at'],
        ];
    }

    // Store companies from the CSV/Excel
    // TODO
    public function upload(Request $request)
    {
        // Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Handle file upload
        $file = $request->file('file');

        return "";
    }

    // Archive the selected companies
    public function archiveSelectedCompany(Request $request)
    {
        // Validate the request to ensure `ids` is an array of integers
        $validated = $request->validate([
            'ids' => 'required',
            'ids.*' => 'integer', // Each item in the array should be an integer
        ]);

        // Find records based on the provided IDs
        $companies = Company::whereIn('id', $validated['ids']);
        $users = User::whereIn('id', $validated['ids']);


        // Soft delete the company records (archiving)
        if ($companies->exists() && $users->exists()) {
            // Soft Deletes
            $companies->delete();
            $users->delete();

            return response()->json(['success' => 'Companies archived successfully'], 200);
        }

        // Return error if no records found
        return response()->json(['error' => 'No companies found with the provided IDs'], 404);
    }

    // Show a company
    public function show(String $company_id)
    {
        // Find company
        $company = Company::has('user')->with('user')->find($company_id);

        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.']);
        }

        // Transform company
        $tranformedCompany = $this->transform($company);

        // Return
        return response()->json($tranformedCompany, 200);
    }

    // Delete a company
    public function destroy(String $company_id)
    {

        // Find Company by ID
        $company = Company::find($company_id);

        // Check if the company exists
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        // Get also the user
        $user = User::find($company_id);

        // Delete Company
        if ($company->delete()) {
            // Delete User
            $user->delete();
        }

        // Return successful delete
        return response()->json([
            'success' => 'The company has been deleted.'
        ]);
    }

    /**
     * A public function that creates a new record of Company
     * - Tables Modified in the function are: companies, and users
     * @param \App\Http\Requests\AdminCompanyRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewCompany(AdminCompanyRequest $request, ActionLogger $actionLogger)
    {

        // Get Validated Request
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
             // Use ActionLogger to log the event
             $actionLogger->logAction(
                actionType: 'Create',
                entity: 'Company',
                entityId: 0,
                description: "Company creation failed.",
                status: 'Failed',
                httpCode: 400,
            );

            // Return status 400 indicating fail creation
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

        // Use ActionLogger to log the event
        $actionLogger->logAction(
            actionType: 'Create',
            entity: 'Company',
            entityId: $user->id,
            description: "Created a new company",
            status: 'Success',
            httpCode: "201",
        );

        // Get Companies
        $companies = $this->table();

        // Return successful insertion
        return response()->json([
            'message' => 'A company is created.',
            'data' => $companies,
        ], 201);
    }

    // A public function that updates a Company record by ID
    public function updateCompanyById(AdminCompanyRequest $request, String $id, ActionLogger $actionLogger)
    {

        // Validated Request
        $validatedCredentials = $request->validated();

        // Find User 
        $user = User::find($id);

        // Check if the user exist
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Find the Company by ID
        $company = Company::find($user['id']);

        // Check if the company exist
        if (!$company) {

            // Use ActionLogger to log the event
            $actionLogger->logAction(
                actionType: 'Update',
                entity: 'Company',
                entityId: $id,
                description: "Company not found, unable to update.",
                status: 'Failed',
                httpCode: "404",
            );

            return response()->json(['message' => 'Company not found'], 404);
        }

        // Update User Credentials
        $user->first_name = $validatedCredentials['first_name'];
        $user->middle_name = $validatedCredentials['middle_name'];
        $user->last_name = $validatedCredentials['last_name'];
        $user->email = $validatedCredentials['email'];
        $user->gender = $validatedCredentials['gender'];
        $user->phone_number = $validatedCredentials['phone_number'];
        $user->street = $validatedCredentials['street'];
        $user->barangay = $validatedCredentials['barangay'];
        $user->city_municipality = $validatedCredentials['city_municipality'];
        $user->province = $validatedCredentials['province'];
        $user->postal_code = $validatedCredentials['postal_code'];

        // Update Company Credentials
        $company->name = $validatedCredentials['company_name'];
        $company->website_url = $validatedCredentials['website_url'];

        // Save Changes
        $user->save();
        $company->save();

        // Use ActionLogger to log the event
        $actionLogger->logAction(
            actionType: 'Update',
            entity: 'Company',
            entityId: $user->id,
            description: "Company is updated.",
            status: 'Success',
            httpCode: "200",
        );

        // Get Companies
        $companies = $this->table();

        // Return successful update response
        return response()->json([
            'message' => 'The company have been updated successfully.',
            'data' => $companies,
        ], 201);
    }    
}
