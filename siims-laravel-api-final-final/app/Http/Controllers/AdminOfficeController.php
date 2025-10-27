<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminOfficeRequest;
use App\Models\Company;
use App\Models\Office;
use App\Models\OfficeType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOfficeController extends Controller
{

    /**
     * Summary of findOffice: Find office by office_id
     * @param string $office_id
     * @return \App\Models\Office
     */
    public function findOffice(String $office_id): Office {

        // Find Office by office_id
        $office = Office::find($office_id);
        if(!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Return office 
        return $office;

    }
    
    /**
     * Summary of update: Update office by office_id
     * @param \App\Http\Requests\AdminOfficeRequest $request
     * @param string $office_id
     * @return JsonResponse|mixed
     */
    public function update(AdminOfficeRequest $request, String $office_id) {

        // Get validated data
        $validatedData = $request->validated();
        // Find Office by office_id

        $office = $this->findOffice($office_id);
        // Update Office
        $office->update($validatedData);
        // Get offices
        $offices = $this->table();
        // Return offices
        return response()->json([
            'message' => 'Office is updated successfully.',
            'data' => $offices
        ], 201);

    }

    /**
     * Summary of findOfficeType: Find office type by office_type_id
     * @param string $office_type_id
     * @return \App\Models\OfficeType
     */
    public function findOfficeType(String $office_type_id): OfficeType {
        // Find Office Type
        $officeType = OfficeType::find($office_type_id);
        // Check if office type exist
        if (!$officeType) {
            return response()->json(['message' => 'Office Type not found.'], 404);
        }

        // Return office type
        return $officeType;
    }

    /**
     * Summary of findCompany: Find company by company_id
     * @param string $company_id
     * @return \App\Models\Company
     */
    public function findCompany(String $company_id): Company {
         // Find Company
         $company = Company::find($company_id);
         // Check if company exist
         if (!$company) {
             return response()->json(['message' => 'Company not found.'], 404);
         }

         // Return company
         return $company;
    }

    /**
     * Summary of store: Create an office
     * @param \App\Http\Requests\AdminOfficeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AdminOfficeRequest $request): JsonResponse
    {
        // Get Validated Credentials
        $validatedCredentials = $request->validated();
        // Find Office Type
        $this->findOfficeType($validatedCredentials['office_type_id']);

        // Find Company
        $this->findCompany($validatedCredentials['company_id']);
        
        // Create Office instance
        $office = new Office();
        // Fill attributes of office
        $office->fill($validatedCredentials);
        // Save Changes
        $office->save();

        // Get offices
        $offices = $this->table();

        // Return status 201 successuly created
        return response()->json(['message' => 'Office created.', 'data' =>  $offices], 201);
    }

    /**
     * Summary of transform: Returns the necessary format of the office.
     * @return array
     */
    public function transform($office): array
    {
        return [
            "id" => $office['id'],
            "company_id" => $office['company_id'],
            "company_name" => $office['company']['name'] ?? 'No company',
            "office_type_id" => $office['officeType']['id'],
            "office_type" => $office['officeType']['name'],
            "office_name" => $office['name'],
            "supervisor_id" => $office['supervisor_id'] ?? "No ID",
            "supervisor_name" => $office['supervisor']['name'] ?? "No supervisor assigned.",
            "phone_number" => $office['phone_number'],
            "street" => $office['street'],
            "barangay" => $office['barangay'],
            "city_municipality" => $office['city_municipality'],
            "province" => $office['province'],
            "postal_code" => $office['postal_code'],
            "created_at" => Carbon::parse($office['created_at'])->format('F j, Y'),
            "updated_at" => Carbon::parse($office['updated_at'])->format('F j, Y'),
            "deleted_at" => $office['deleted_at'],
        ];
    }

    /**
     * Summary of table: Table format for Admin Office
     * @return Collection|\Illuminate\Support\Collection
     */
    public function table() {
        // Get all offices that has company
        $offices = Office::with(['company', 'supervisor', 'officeType'])->has('company')->get();

        // Mapping
        $offices = $offices->map(function ($office) {
            return $this->transform($office);
        });

        // Return offices
        return $offices;
    }

    /**
     * Summary of index: Fetch all offices including it's supervisor, company and office type.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        // Get all offices that has company
        $offices = $this->table();

        // Return offices
        return response()->json($offices);
    }
}
