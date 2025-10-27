<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanySupervisorRequest;
use App\Models\Office;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanySupervisorController extends Controller
{

    /**
     * Summary of transform: Format of required attributes for Supervisor
     * @param mixed $supervisor
     * @return array
     */
    public function transform($supervisor): array {
        return [
            "id" => $supervisor['id'],
            "office_assigned" => $supervisor['office']['name'] ?? "Not assigned",
            "first_name" => $supervisor['user']['first_name'],
            "middle_name" => $supervisor['user']['middle_name'],
            "last_name" => $supervisor['user']['last_name'],
            "email" => $supervisor['user']['email'],
            "gender" => $supervisor['user']['gender'],
            "phone_number" => $supervisor['user']['phone_number'],
            "street" => $supervisor['user']['street'],
            "barangay" => $supervisor['user']['barangay'],
            "city_municipality" => $supervisor['user']['city_municipality'],
            "province" => $supervisor['user']['province'],
            "postal_code" => $supervisor['user']['postal_code'],
            "created_at" => $supervisor['user']['created_at'],
            "updated_at" => $supervisor['user']['updated_at'] ?? 'No updates',
        ];  
    }

    /**
     * Summary of table: Gets the list of supervisors based on Auth User ID and also the required attributes
     * @return Collection|JsonResponse|mixed|\Illuminate\Support\Collection
     */
    public function table() {
        // Get Auth User
        $company_user = Auth::user();

        // Get Supervisors by Company ID
        $supervisors = Supervisor::with(['office', 'user'])->where('company_id', '=', $company_user->id)->get();
        // Check if Supervisors does Exist
        if(!$supervisors) {
            return response()->json(['message' => 'Supervisors not found.'], 404);
        }

        // Transform Supervisors
        $supervisors = $supervisors->map(function ($supervisor) {
            return $this->transform($supervisor);
        });
        
        // Return Supervisors
        return $supervisors;
    }

    /**
     * Summary of index: Returns a collections of Supervisors from the Company
     * @return Collection
     */
    public function index() {

        $supervisors = $this->table();

        return response()->json($supervisors);
    }

    /**
     * Summary of findOffice: Finds the Office based on the Auth User ID
     * @param string $office_id
     * @return \App\Models\Office
     */
    public function findOffice(String $office_id): Office {

        // Get Auth User
        $user = Auth::user();
        // Get Office based on Auth User ID
        $office = Office::where('company_id', '=', $user->id)->find($office_id);
        // Check if office exist
        if(!$office) {
            return response()->json(['message'=> 'Office not found.'], 404);
        }
        // Return Office
        return $office;

    }

    /**
     * Summary of update: Updates the Supervisor based on the ID
     * @param \App\Http\Requests\CompanySupervisorRequest $request
     * @param string $supervisor_id
     * @return JsonResponse|mixed
     */
    public function update(CompanySupervisorRequest $request, String $supervisor_id)  {

        // Get validated credentials
        $validatedCredentials = $request->validated();
        // Get Auth User
        $company_user = Auth::user();
        // Find Supervisor based on Auth User ID first then the supervisor_id
        $supervisor = Supervisor::with(['office', 'user'])->where('company_id', '=', $company_user->id)->find($supervisor_id);
        // Check if Supervisor exist
        if(!$supervisor) {
            return response()->json(['message' => 'Supervisor not found.'], 404);
        }

        // Get User
        $user = $supervisor->user;

        // Update User Personal Details
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

        // Save Changes for User    
        $user->save();

        // Check for old office and remove supervisor from it if necessary
        $oldOffice = $supervisor->office;
        if($oldOffice && isset($validatedCredentials['office_id'])) {
            // Remove supervisor from old office
            $oldOffice->supervisor_id = null;
            $oldOffice->save();
        }

        // Handle office transfer
        if(isset($validatedCredentials['office_id'])) {
            $newOffice = $this->findOffice($validatedCredentials['office_id']);

            // Assign supervisor to the new office
            $newOffice->supervisor_id = $supervisor['id'];
            $newOffice->save();
        }

        // Return updated Supervisor with related data

        // Get Supervisor List
        $supervisors = $this->table();

        // Return Supervisor
        return response()->json(['message' => 'Supervisor updated successfully', 'data' => $supervisors], 201);
        
    }

    /**
     * Summary of store: Returns a JsonResponse of collections of supervisors from the company after creating a Supervisor
     * @param \App\Http\Requests\CompanySupervisorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CompanySupervisorRequest $request): JsonResponse {
        // Get Auth Company User
        $company_user = Auth::user();

        // Get Validated Credentials
        $validatedCredentials = $request->validated();
      
        // Search Office based on the Auth User
        $office = Office::where('company_id', '=', $company_user->id)->where('id', '=', $validatedCredentials['office_id'])->first();

        // Check if office exists and is part of the authenticated user's company
        if(!$office) {
            return response()->json(['message' => 'Office not found'], 404);
        }
        // Check if office is occupied
        if($office['supervisor_id']) {
            return response()->json(['message' => 'This office already has a supervisor.'], 400);
        }

         // Create User Instance
        $supervisor = new User();
        // Fill User
        $supervisor->fill($validatedCredentials);
        // Save User
        $supervisor->save();
        
        // Check if user is created
        if($supervisor) {
            // Create Supervisor Role
            UserRole::create([
                "user_id" => $supervisor['id'],
                "role_id" => 8,
            ]);
            // Create Supervisor Model
            Supervisor::create([
                "id" => $supervisor['id'],
                "user_id" => $supervisor['id'],
                "company_id" => $company_user['id'],
            ]);

            // Update Office of the user
            $office->supervisor_id = $supervisor['id'];
            // Save Changes
            $office->save();
        }

        // Get Supervisor
        $supervisors = $this->table();

        // Return Supervisors
        return response()->json([
            "message" => 'Supervisor is created.',
            "data" => $supervisors,
        ], 201);
    }
}
