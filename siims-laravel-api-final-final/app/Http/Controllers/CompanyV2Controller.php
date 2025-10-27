<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Http\Resources\CompanyV2Resource;
use App\Http\Resources\SearchCompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyV2Controller extends UserV2Controller
{

    // Log Controller
    private $logController;

    public function __construct(LogController $logController) {
        $this->logController = $logController;
    }

    /**
     * Summary of searchCompany: A public function that searches the company by ID.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchCompany(Request $request) {

        $query = $request->input('query');

        $companies = Company::whereHas('user', function ($q) use ($query) {
            $q->where('first_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('middle_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('last_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('email', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('id', 'LIKE', '%' . $query . '%'); // Missing semicolon added here
        })->orWhere('name', 'LIKE', '%' . $query . '%')->get();

        // Transform the resources
        $companyResources = SearchCompanyResource::collection($companies);

        // Return
        return $this->jsonResponse($companyResources, 200);
    }

    /**
     * Summary of restoreCompanyByID: A public function that restores a company by ID.
     * @param string $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreCompanyByID(String $company_id) {

        // Find Company
        $company = Company::withTrashed()->where('user_id', $company_id)->first();

           // Add new log
           $this->logController->addNewLog(
            'Restores a company',
            'Company',
            $company->id,
            'attempts to restore a company',
            '200',
        );

        // Restore the company
        $company = $this->findAndRestoreModel($company_id, Company::class);

         // Return 
         return $this->jsonResponse([
            'message' => 'Company Restored',
            'type' => 'restore',
        ], 201);
        
    }


    /**
     * Summary of deleteCompanyByID: Delete company by ID.
     * @param string $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCompanyByID(String $company_id)
    {

        // Find Company
        $company = Company::where('user_id', $company_id)->first();


        // Add new log
        $this->logController->addNewLog(
            'Deletes a company',
            'Company',
            $company->id,
            'attempts to delete a company',
            '200',
        );

        // Delete Company
        $company->delete();

        // Return response with status 201
        return $this->jsonResponse(['message' => "Company is deleted."], 201);
    }

    /**
     * Summary of getAllCompanies: A public function that fetches all companies.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllCompanies(Request $request)
    {

        // Define the status
        $status = (string) $request->input('status');

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        /**
         * 
         * Get all companies
         * 
         * Conditions:
         *
         * - Get only soft deleted companies if the status (request) is archived
         * - Get all companies except for those soft deleted companies.
         * 
         * 
         */
        $query = $status === 'archived' ? Company::onlyTrashed()->with('user')->withCount(['supervisors']) : Company::with('user')->withCount(['supervisors']);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {

            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            })->OrWhere('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $companies = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $companiesResources = CompanyV2Resource::collection($companies);

        // Add new log
        $this->logController->addNewLog(
            'Views the list of companies',
            'Company',
            'N/A',
            'attempts to view the list of companies.',
            '200',
        );

        // Return resources
        return $companiesResources;
    }
}
