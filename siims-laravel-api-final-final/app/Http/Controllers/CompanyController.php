<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends UserController
{

    /**
     * The user role controller
     */
    private $userRoleController;

    /**
     * CoordinatorController constructor.
     */
    public function __construct(UserRoleController $userRoleController)
    {
        $this->userRoleController = $userRoleController;
    }
    
    /**
     * A private function that fetch all companies.
     */
    private function fetchCompanies() {

        // Get authenticated user
        $user = Auth::user();

        // Declare variable query
        $query = null;

         // ! For Admin
         if($user->hasRole('admin')) {
            
            // Get All User (Company) Info
            $query = Company::withTrashed()->with(['user'])->withCount(['supervisors']);
         
        }
      
        // ! Other Users
        else {
            // Get All User (Company) Info
            $query = Company::with(['user'])->withCount(['supervisors']);
        }

        // Return query
        return $query;

    }

     /**
     * A public function that query finds a company by ID.
     */
    public function queryFindCompanyByID(String $companyID) {

        // Find company by ID
        $query = $this->fetchCompanies()->where('id', $companyID);


        // Return query
        return $query;

    }


    /**
     * A public function that finds a company by ID.
     */
    public function findCompanyByID(String $companyID) {

        // Find company by ID
        $company = $this->fetchCompanies()->find($companyID);

        // Return company 
        return $company;

    }
    
    /**
     * Summary of updateCompanyById: A public function that updates a company by ID.
     * @param \App\Http\Requests\CompanyRequest $request
     * @param string $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCompanyById(CompanyRequest $request, String $company_id) {

        // Get validated
        $validated = $request->validated();

        // Find Company by ID
        $company = $this->findCompanyByID($company_id);

        // Update Company and User
        $company->update($validated);
        $company->user->update($validated);
        $company->save();

        // Return resource
        return $this->jsonResponse([
            "message" => 'A company is updated.',
            "data" => new CompanyResource($company),
        ], 201);

    }
    
    /**
     * Summary of deleteCompanyByID: A public function that deletes a company by ID.
     * @param string $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCompanyByID(String $company_id) {

        // Find Company and User
        $company = Company::find($company_id);
        // $user = User::find($company_id);

        // Delete Company, User, and roles
        $company->delete();
        // $user->delete();
        // $userRoles->delete(); 

        // Return response with status 201
        return $this->jsonResponse(['message' => "Company is deleted."], 201);
    }

    /**
     * Summary of getAllCompanies: A public function that gets all companies.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllCompanies(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);


        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all companies
        $query = $this->fetchCompanies();
        
        // Apply the search filter if search term is provided
        if(!empty($searchTerm)) {   
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $companies = $query->paginate($perPage);
        
        // Transform the paginated data into a resource collection
        $companiesResources = CompanyResource::collection($companies);

        // Return resources
        return $companiesResources;
    }

    /**
     * Summary of addNewCompany: A public function that adds new company.
     * @param \App\Http\Requests\CompanyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewCompany(CompanyRequest $request) {

        // Get validated
        $validated = $request->validated();

        // Create a new User
        $user = $this->addNewUser($validated);

        // Check if user is created
        if(!$user) {
            return $this->jsonResponse([
                'message' => 'Something is wrong.',
            ], 400);
        }

        // Create Company
        Company::create([
            "id" => $user->id,
            "user_id" => $user->id,
            "website_url" => $validated['website_url'],
            "name" => $validated['name'],
        ]);

        // Create User Role
        $this->userRoleController->addUserRole($user->id, 4); // Company role ID

        // Find Company
        $company = $this->findCompanyByID($user->id);

        // Return new created resource
        return $this->jsonResponse([
            'message' => 'Company is created',
            'data' => new CompanyResource($company),
        ], 201);

    }

    // Get User Profile
    public function profile()
    {

        // Get Auth User
        $user = Auth::user();

        // Get Company and User Data
        $profile = Company::has('user')->with(['user'])->find($user['id']);

        // Transform Profile
        $profile = [
            "id" => $profile['id'],
            "company_name" => $profile['name'],
            "website_url" => $profile['website_url'],
            "first_name" => $profile['user']['first_name'],
            "middle_name" => $profile['user']['middle_name'],
            "last_name" => $profile['user']['last_name'],
            "email" => $profile['user']['email'],
            "gender" => $profile['user']['gender'],
            "phone_number" => $profile['user']['phone_number'],
            "street" => $profile['user']['street'],
            "barangay" => $profile['user']['barangay'],
            "city_municipality" => $profile['user']['city_municipality'],
            "province" => $profile['user']['province'],
            "postal_code" => $profile['user']['postal_code'],
            "created_at" => $profile['created_at'],
            "updated_at" => $profile['updated_at'],
        ];

        // Return
        return response()->json($profile, 200);
    }
}
