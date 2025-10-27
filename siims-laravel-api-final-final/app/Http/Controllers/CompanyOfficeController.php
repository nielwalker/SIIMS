<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyOfficeRequest;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\Supervisor;
use App\Models\WorkPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyOfficeController extends Controller
{

    

    /**
     * A public function that deletes the office by id
     * @param string $office_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function deleteOfficeById(String $office_id)
    {

        // Get auth user
        $user = Auth::user();

        // Find office where user id match office_id's and find the office id
        $office = Office::where('company_id', $user->id)->find($office_id);

        // Check if office exist
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Delete office
        $office->delete();

        // Return response 201
        return response()->json(['message' => 'Office is deleted.'], 201);
    }

    /**
     * Summary of update: Update office by office_id
     * @param \App\Http\Requests\CompanyOfficeRequest $request
     * @param string $office_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateOfficeById(CompanyOfficeRequest $request, String $office_id)
    {
        // Get validated request
        $validatedData = $request->validated();

        // Get user
        $user = Auth::user();

        // Get office from the user
        $office = Office::where('company_id', $user['id'])->with(['officeType', 'supervisor'])->find($office_id);
        // Check if office exist
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }


        // Update Office attributes
        $office->update($validatedData);

        // Return offices
        return response()->json(['message' => 'Office updated successfully']);
    }

    /**
     * Summary of transform: Necessarry Attributes for Company's Office
     * @param mixed $office
     * @return array
     */
    public function transform($office)
    {

        return [
            "id" => $office['id'],
            "name" => $office['name'],
            "office_type_id" => $office->officeType->id,
            "office_type" => $office->officeType->name ?? null,
            "supervisor_name" => $office->supervisor
                ? trim($office->supervisor->user->first_name . ' ' . $office->supervisor->user->middle_name . ' ' . $office->supervisor->user->last_name)
                : "",
            "phone_number" => $office['phone_number'],
            "street" => $office->street,
            "barangay" => $office->barangay,
            "city_municipality" => $office->city_municipality,
            "province" => $office->province,
            "postal_code" => $office->postal_code,
            "full_address" => "{$office->street}, {$office->barangay}, {$office->city_municipality}, {$office->province}, {$office->postal_code}",
            "office_type_id" => $office->officeType->id,
            "supervisor_id" => $office['supervisor_id'],
            "created_at" => $this->formatDate($office->created_at), // Reformat the date
            "updated_at" => $this->formatDate($office->updated_at), // Reformat the date
        ];
    }

    /**
     * Summary of transformSupervisor: Transform Supervisor
     * @param mixed $supervisor
     * @return array
     */
    private function transformSupervisor($supervisor) {
        return $supervisor ? [
            "id" => $supervisor['id'],
            "full_name" => trim($supervisor->user->first_name . ' ' . $supervisor->user->middle_name . ' ' . $supervisor->user->last_name)
        ] : "";
    }

    /**
     * Summary of getOfficeById: Return an office by office_id
     * @param string $office_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOfficeById(String $office_id)
    {

        // Get user
        $user = Auth::user();

        // Get office from the user
        $office = Office::where('company_id', $user['id'])->with(['officeType', 'supervisor'])->find($office_id);
        // Check if office exist
        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        // Transform Office
        $office = $this->transform($office);

        // Get the supervisor assigned in the office
        $supervisor = Supervisor::with(['user'])->find($office['supervisor_id']);
        $transformedSupervisor = $this->transformSupervisor($supervisor);

        // Get supervisors belong in the company
        $supervisors = Supervisor::where('company_id', $user['id'])->get();

        // Get work posts
        $workPosts = WorkPost::where('office_id', $office['id'])->get();


        // Return Office
        return response()->json([
            "initial_office" => $office,
            "supervisor_assigned" => $transformedSupervisor,
            "supervisors" => $supervisors,
            "work_posts" => $workPosts,
            "office_types" => OfficeType::all(),
        ], 200);
    }

    /**
     * Summary of index: Get all offices from the user (company)
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllOffices()
    {

        // Get user
        $user = Auth::user();

        // Get office from the user
        $offices = Office::where('company_id', $user['id'])->with(['officeType', 'supervisor.user'])->get();

        // Transform Office
        $offices = $offices->map(function (Office $office) {
            return $this->transform($office);
        });

        // Return Offices
        return response()->json($offices, 200);
    }

    /**
     * Summary of store: Store new office
     * @param \App\Http\Requests\CompanyOfficeRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewOffice(CompanyOfficeRequest $request)
    {

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Get user
        $user = Auth::user();

        // Add the user id
        $validatedCredentials['company_id'] = $user['id'];

        // Store new office
        $office = Office::create($validatedCredentials);

        // Check if created
        if (!$office) {
            return response()->json(['message' => "Something's wrong during creating an office."]);
        }

        // Return response
        return response()->json(['message' => "An office is created"], status: 201);
    }
}
