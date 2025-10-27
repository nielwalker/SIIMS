<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Company;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeController extends Controller
{

    /**
     * Summary of assignSupervisorById: A public function that assigns a Supervisor to an office
     * @param string $supervisorID - The ID of the Supervisor 
     * @param string $officeID - The ID of the Office
     * @return void
     */
    public function assignSupervisorById(String $supervisorID, String $officeID)
    {

        // Check if the supervisor is already assigned to an office
        $currentOffice = Office::where('supervisor_id', $supervisorID)->first();

        if ($currentOffice) {
            // Remove the supervisor from the current office
            $currentOffice->supervisor_id = null;
            $currentOffice->save();
        }

        // Find Office
        $office = $this->findOffice($officeID);

        // Update and save
        $office->supervisor_id = $supervisorID;
        $office->save();
    }

    /**
     * Summary of findOffice; A public function that finds the office by ID.
     * @param string $officeID
     * @return TModel
     */
    public function findOffice(String $officeID)
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Declare empty variable
        $office = null;

        // ! FOR COMPANY
        if ($authUser->hasRole('company')) {

            // Get office that belongs to a company
            $office = Office::where('company_id', $authUser->id)->find($officeID);
        }

        // ! OTHER ROLES
        else {
            // Get office
            $office = Office::find($officeID);
        }

        // Check if office exist
        if (!$office) {
            abort(404, 'Office not found');
        }

        // Return office
        return $office;
    }

    /**
     * Summary of findOfficeIdById: Find the ID of office by ID
     * @param string $officeID
     * @return mixed
     */
    public function findOfficeIdById(String $officeID)
    {

        // Find Office ID by ID
        $officeID = $this->findOffice($officeID)->id;

        // Return office ID
        return $officeID;
    }

    /**
     * Summary of getAllListsOfOffices: A public function that gets the list of offices
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllListsOfOffices()
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Declare empty variable
        $listOfOffices = null;

        // ! FOR COMPANY
        if ($authUser->hasRole('company')) {

            /**
             * Find offices that belongs to a company ($authUser->id)
             */
            $listOfOffices = Office::with(['supervisor.user'])->where('company_id', $authUser->id)->get();
        }

        // ! FOR ADMIN
        else {

            // Find all list of offices
            $listOfOffices = Office::with(['supervisor.user'])->get();
        }

        // Transform offices
        $transformedListOfOffices = $listOfOffices->map(function ($office) {
            return [
                "id" => $office->id,
                "name" => $office->name,
                "supervisor_name" => $office->supervisor ? $this->getFullName(
                    $office->supervisor->user->first_name ?? "",
                    $office->supervisor->user->middle_name ?? "",
                    $office->supervisor->user->last_name ?? "",
                ) : ""
            ];
        });

        // Return status and data
        return $this->jsonResponse($transformedListOfOffices, 200);
    }

    // Get all offices
    public function index(String $company_id)
    {

        // Get authenticated user
        $user = Auth::user();

        // Get offices
        $offices = Office::where('company_id', $user['id'])->get();

        // Return
        return $offices;
    }

    /**
     * Summary of getAllOfficesArchives: A public function that gets all only archived offices.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllOfficesArchives(Request $request) {
        // Get authenticated user
        $authUser = Auth::user();

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // * Initialize variable
        $query = null;

        /**
         * Check the role
         */
        // ! FOR ADMIN
        if($authUser->hasRole('admin')) {

            // Fetch input of id
            $companyID = $request->input('companyID');

            // Query: Fetch all offices that belongs to the company
            $query = Office::onlyTrashed()->with(['officeType'])->where('company_id', $companyID);

        }
        // ! FOR COMPANY
        else {
            // Query: Fetch all offices that belongs to the company
            $query = Office::onlyTrashed()->with(['officeType'])->where('company_id', $authUser->id);
        }

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $offices = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $officesResources = OfficeResource::collection($offices);

        // Return resources
        return $officesResources;
    }

    /**
     * Summary of getAllOffices: A public function that gets the offices that belongs to a specific company.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllOffices(Request $request) {

        // Get authenticated user
        $authUser = Auth::user();

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // * Initialize variable
        $query = null;

        /**
         * Check the role
         */
        // ! FOR ADMIN
        if($authUser->hasRole('admin')) {

            // Fetch input of id
            $companyID = $request->input('companyID');

            // Query: Fetch all offices that belongs to the company
            $query = Office::with(['officeType', 'supervisor.user'])->where('company_id', $companyID)->withCount(['workPosts']);

        }
        // ! FOR COMPANY
        else {
            // Query: Fetch all offices that belongs to the company
            $query = Office::with(['officeType', 'supervisor.user'])->where('company_id', $authUser->id)->withCount(['workPosts']);
        }

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
        }

        // Paginate the results
        $offices = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $officesResources = OfficeResource::collection($offices);

        // Return resources
        return $officesResources;

    }

    // Get office by company
    public function getOfficeByCompanyId(String $company_id, String $office_id)
    {

        // Get office
        $office = Office::find($office_id);

        // Check if office exist
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Return
        return response()->json($office);
    }

    // Create office by Company Id
    public function storeOfficeByCompanyId(OfficeRequest $request, String $company_id)
    {

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Get Company
        $company = Company::find($company_id);
        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Store new office
        $office = Office::create($validatedCredentials);

        // Check if successful creation
        if (!$office) {
            return response()->json(['message' => 'Office not created.'], 404);
        }

        // Return response
        return response()->json(['message' => 'The office is created']);
    }

    // Get office by company id
    public function getOfficesByCompanyId(String $company_id)
    {
        // Get offices
        $offices = Office::where('company_id', $company_id)->get();

        // Return
        return response()->json($offices, 200);
    }

    // Delete office by ID
    public function deleteOfficeById(String $office_id) {

        // Find Office by ID
        $office = Office::find($office_id);

        // Check if office does exist
        if(!$office) {

            return $this->jsonResponse(['message' => 'Office not found'], 404);

        }

        // Delete office
        $office->delete();

        // Return message and status
        return $this->jsonResponse(['message' => 'Office is deleted.'], 201);

    }

    // Get an office
    public function show(String $company_id, String $office_id)
    {

        $office = Office::findOrFail($office_id);

        return $office;
    }

    // Create an office
    public function store(OfficeRequest $request)
    {

        $validatedCredentials = $request->validated();

        // Store new office record
        Office::create($validatedCredentials);

        // Return success
        // Return successful update
        return response()->json([
            'success' => 'The office has been created.'
        ]);
    }
}
