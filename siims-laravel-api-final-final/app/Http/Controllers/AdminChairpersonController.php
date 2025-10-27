<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminChairpersonRequest;
use App\Models\Office;
use App\Models\Program;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminChairpersonController extends Controller
{

    /**
     * Summary of findChairperson: Find Chairperson by ID
     * @param string $chairperson_id
     * @return JsonResponse|mixed|TModel|\Illuminate\Database\Eloquent\Collection
     */
    public function findChairperson(String $chairperson_id): Collection {
        // Find Chairperson
        $chairperson = User::find($chairperson_id);

        // Check if person does exist
        if (!($chairperson && $chairperson->roles()->where('name', 'chairperson')->exists())) {
            return response()->json(['message' => 'Chairperson not found.'], 404);
        }

        // Return Chairperson
        return $chairperson;
    }

    // Archive Chairperson
    public function archive(String $chairperson_id) {

        // Find Chairperson 
        $chairperson = $this->findChairperson($chairperson_id);

        // Delete Chairperson
        $chairperson->delete();

        // Get Chairpersons
        $chairpersons = $this->table();

        // Return Chairperson
        return response()->json([
            "message" => 'Chairperson is deleted',
            "data" => $chairpersons
        ], 201);

    }

    // Find Program
    public function findProgram(String $program_id)
    {
        // Find Program
        $program = Program::find($program_id);

        // Check if program exist
        if (!$program) {
            return response()->json(['message' => 'Program not found.'], 404);
        }

        // Return Program
        return $program;
    }

    // Update a Chairperson 
    public function update(AdminChairpersonRequest $request, String $chairperson_id)
    {

        // Get Validated Credentials
        $validatedCredentials = $request->validated();

        // Find Chairperson
        $chairperson = $this->findChairperson($validatedCredentials['id']);

        // Update chairperson's personal details
        $chairperson->first_name = $validatedCredentials['first_name'];
        $chairperson->middle_name = $validatedCredentials['middle_name'];
        $chairperson->last_name = $validatedCredentials['last_name'];
        $chairperson->email = $validatedCredentials['email'];
        $chairperson->gender = $validatedCredentials['gender'];
        $chairperson->phone_number = $validatedCredentials['phone_number'];
        $chairperson->street = $validatedCredentials['street'];
        $chairperson->barangay = $validatedCredentials['barangay'];
        $chairperson->city_municipality = $validatedCredentials['city_municipality'];
        $chairperson->province = $validatedCredentials['province'];
        $chairperson->postal_code = $validatedCredentials['postal_code'];

        // Handle program transfer
        $currentProgram = Program::where('chairperson_id', '=', $chairperson->id)->first();
        $newProgram = Program::find($validatedCredentials['program_id']);

        // Check if the new program exists
        if (!$newProgram) {
            return response()->json(['error' => 'Program not found.'], 404);
        }

        // If the chairperson is currently assigned to a different program, remove them from the old program
        if ($currentProgram && $currentProgram->id !== $newProgram->id) {
            // Remove the chairperson from the current program
            $currentProgram->chairperson_id = null;
            $currentProgram->save();
        }

        // Check if the new program already has a chairperson
        if ($newProgram->chairperson_id && $newProgram->chairperson_id !== $chairperson->id) {
            return response()->json(['error' => 'The new program is already occupied by another chairperson.'], 400);
        }

        // Assign the chairperson to the new program
        $newProgram->chairperson_id = $chairperson->id;

        // Save the updated chairperson details and the new program assignment
        $chairperson->save();
        $newProgram->save();

        // Get Chairperson
        $chairpersons = $this->table();

        // Return Chairpersons
        return response()->json($chairpersons, 201);
    }

    /**
     * Summary of table: Table Format of Chairperson
     * @return JsonResponse|mixed|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function table()
    {
        // Get all users with the role of chairperson and their assigned programs
        $chairpersons = User::with('program')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'chairperson');
            })
            ->get();


        // Check if the collection is empty
        if ($chairpersons->isEmpty()) {
            return response()->json(['message' => 'Chairpersons not found.'], 404);
        }

        // Transform Chairperson
        $chairpersons = $chairpersons->map(function ($chairperson) {
            return [
                "id" => $chairperson['id'],
                "program_name" => $chairperson['program']['name'] ?? "No program assigned.",
                "first_name" => $chairperson['first_name'],
                "middle_name" => $chairperson['middle_name'],
                "last_name" => $chairperson['last_name'],
                "email" => $chairperson['email'],
                "email_verified_at" => $chairperson['email_verified_at'],
                "gender" => $chairperson['gender'],
                "phone_number" => $chairperson['phone_number'],
                "street" => $chairperson['street'],
                "barangay" => $chairperson['barangay'],
                "province" => $chairperson['province'],
                "postal_code" => $chairperson['postal_code'],
                "created_at" => $chairperson['created_at'],
                "updated_at" => $chairperson['updated_at'],
                "deleted_at" => $chairperson['deleted_at'],
            ];
        });

        // Return the chairpersons with their programs
        return $chairpersons;
    }

    public function store(AdminChairpersonRequest $request)
    {
        

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find Program
        $program = $this->findProgram($validatedCredentials['program_id']);

        // Check if program is already occupied by another chairperson
        if ($program['chairperson_id']) {
            return response()->json(['message' => 'Program is already occupied by another chairperson.']);
        }

        // Store new chairperson
        $chairperson = User::create([
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
        ]);

        // Check if the chairperson is created
        if (!$chairperson) {
            return response()->json(['message' => 'Unable to create new chairperson.']);
        }

        // Assign Role
        // chairperson no role: 2
        UserRole::create([
            'user_id' => $chairperson['id'],
            'role_id' => 2,
        ]);

        // Update program with chairperson
        $program['chairperson_id'] = $chairperson['id'];

        // Save changes
        $program->update();

        // Get Chairperson
        $chairpersons = $this->table();

        // Return
        return response()->json([
            "data" => $chairpersons,
            'message' => 'A new chairperson is created.'
        ], 201);
    }

    /**
     * Summary of index: Gets the Chairperson
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {

        // Get all user has a role of chairperson
        $chairpersons = $this->table();

        // Return
        return response()->json($chairpersons, 200);
    }
}
