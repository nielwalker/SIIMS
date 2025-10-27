<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyListOfficesResource;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficeV2Controller extends Controller
{


    /**
     * Summary of findOfficeByID: Find office by ID.
     * @param string $office_id
     * @return TModel|null
     */
    public function findOfficeByID(String $office_id)
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Declare empty variable
        $office = $authUser->hasRole('company') ? Office::where('company_id', $authUser->id)->find($office_id) : Office::find($office_id);

        // Return
        return $office;
    }

    /**
     * Summary of assignSupervisorById: A public function that assigns a supervisor to the office by ID.
     * @param string $supervisor_id
     * @param string $office_id
     * @return void
     */
    public function assignSupervisorById(String $supervisor_id, String $office_id)
    {

        // Find Office
        $office = $this->findOfficeByID($office_id);

        // Update and save
        $office->supervisor_id = $supervisor_id;
        $office->save();
    }

    /**
     * Summary of getAllCompanyOffices: A public function that gets all list of company offices
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCompanyOffices()
    {

        // Get authenticated user 
        $authUser = Auth::user();

        // Get all offices from company
        $offices = Office::where('company_id', $authUser->company->id)->get();

        // Transform to collection
        $transformedOffices = CompanyListOfficesResource::collection($offices);

        // Return
        return $this->jsonResponse($transformedOffices);
    }
}
