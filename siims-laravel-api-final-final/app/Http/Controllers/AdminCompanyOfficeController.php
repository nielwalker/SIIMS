<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCompanyOfficeRequest;
use App\Models\Company;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCompanyOfficeController extends Controller
{

    public function deleteOfficeById(String $company_id, String $office_id)
    {
        // Find company by company_id
        $company = Company::find($company_id);
        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Find office by ID
        $office = Office::where('company_id', $company->id)->find($office_id);
        // Check if office does exist
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Delete office
        $office->delete();

        return response()->json(['message' => 'Office is deleted.'], 201);
    }

    /**
     * A public function that updates an office by ID.
     * @param \App\Http\Requests\AdminCompanyOfficeRequest $request
     * @param string $company_id
     * @param string $office_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateOfficeById(AdminCompanyOfficeRequest $request, String $company_id, String $office_id)
    {

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find company by company_id
        $company = Company::find($company_id);
        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Find office by ID
        $office = Office::where('company_id', $company->id)->find($office_id);
        // Check if office does exist
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // If supervisor_id exists
        if (isset($validatedCredentials['supervisor_id'])) {
            // Find supervisor by supervisor_id
            $supervisor = User::find($validatedCredentials['supervisor_id']);

            // Check if supervisor exists
            if (!$supervisor) {
                return response()->json(['message' => 'Supervisor not found.'], 404);
            }

            // Check if supervisor is already assigned to another office
            $existingOffice = Office::where('supervisor_id', $validatedCredentials['supervisor_id'])->first();

            if ($existingOffice && $existingOffice->id !== $office->id) {
                // If the supervisor is already assigned to another office, return an error
                return response()->json(['message' => 'This supervisor is already assigned to another office.'], 400);
            }

            // If the supervisor_id is valid and can be assigned, update the office record
            $office->supervisor_id = $validatedCredentials['supervisor_id'];
        }

        // Update other office details
        $office->update([
            "office_type_id" => $validatedCredentials['office_type_id'],
            "name" => $validatedCredentials['name'],
            "phone_number" => $validatedCredentials['phone_number'],
            "street" => $validatedCredentials['street'],
            "barangay" => $validatedCredentials['barangay'],
            "city_municipality" => $validatedCredentials['city_municipality'],
            "province" => $validatedCredentials['province'],
            "postal_code" => $validatedCredentials['postal_code'],
        ]);
        $office->save();

        // Get office and transform office
        $transformedOffice = $this->transform(Office::with(['officeType', 'supervisor.user'])->find($office->id));

        return response()->json(['message' => 'Office is updated.', "data" => $transformedOffice], 201);
    }

    /**
     * A pubic function that adds new office record.
     * @param \App\Http\Requests\AdminCompanyOfficeRequest $request
     * @param string $company_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewOffice(AdminCompanyOfficeRequest $request, String $company_id)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find company by company_id
        $company = Company::find($company_id);
        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Create a new office record
        $office = Office::create([
            "office_type_id" => $validatedCredentials['office_type_id'],
            "company_id" => $company_id,
            "name" => $validatedCredentials['name'],
            "phone_number" => $validatedCredentials['phone_number'],
            "street" => $validatedCredentials['street'],
            "barangay" => $validatedCredentials['barangay'],
            "city_municipality" => $validatedCredentials['city_municipality'],
            "province" => $validatedCredentials['province'],
            "postal_code" => $validatedCredentials['postal_code'],
        ]);

        // Check if office record is created
        if (!$office) {
            return response()->json(['message' => 'Unable to create new office.'], 400);
        }
    
         // Get office and transform office
         $transformedOffice = $this->transform(Office::with(['officeType', 'supervisor.user'])->find($office->id));

        // Return office
        return response()->json(['message' => 'A new office is created.', 'data' => $transformedOffice], 201);
    }

    /**
     * A private function that transforms the office attributes
     * @param mixed $office
     * @return array
     */
    private function transform($office)
    {

        return [
            "id" => $office->id,
            "name" => $office->name,
            "office_type" => $office->officeType ? $office->officeType->name : null,
            "supervisor_name" => $office->supervisor ? trim($office->supervisor->user->first_name . ' ' . $office->supervisor->user->middle_name . ' ' . $office->supervisor->user->last_name) : null,
            "phone_number" => $office->phone_number,
            "street" => $office->street,
            "barangay" => $office->barangay,
            "city_municipality" => $office->city_municipality,
            "province" => $office->province,
            "postal_code" => $office->postal_code,
            "created_at" => $office->created_at,
            "updated_at" => $office->updated_at,
            "office_type_id" => $office->office_type_id,
            "supervisor_id" => $office->supervisor ? $office->supervisor->id : null,

        ];
    }

    /**
     * A public function that gets all company's offices
     * @param string $company_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllOffices(String $company_id)
    {

        // Find company by company_id
        $company = Company::find($company_id);
        // Check if company exist
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Find all offices by id
        $offices = Office::where('company_id', $company['id'])->with(['officeType', 'supervisor.user'])->get();

        // Check if offices does exist
        if (!$offices) {
            return response()->json(['message' => 'Offices not found.']);
        }

        // Transform offices
        $transformedOffices = $offices->map(function ($office) {
            return $this->transform($office);
        });

        // Get office types
        $office_types = OfficeType::all();

        // Get all supervisors based on company id
        $supervisors = Supervisor::with(['user'])->where('company_id', $company_id)->get();
        $transformedSupervisors = $supervisors->map(function ($supervisor) {
            return $this->transformSupervisor(supervisor: $supervisor);
        });

        return response()->json(['initial_offices' => $transformedOffices, "company" => $company, "office_types" => $office_types, 'supervisors' => $transformedSupervisors], 200);
    }

    /**
     * A function that transforms the supervisor
     * @param mixed $supervisor
     * @return array
     */
    private function transformSupervisor($supervisor) {

        return [
            "id" => $supervisor->id,
            "full_name" => $supervisor->user ? trim($supervisor->user->first_name . ' ' . $supervisor->user->middle_name . ' ' . $supervisor->user->last_name) : null
        ];

    }
}
