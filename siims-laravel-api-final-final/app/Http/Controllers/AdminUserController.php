<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUserRequest;
use App\Models\College;
use App\Models\Company;
use App\Models\Coordinator;
use App\Models\Office;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{

    // Table format for Users
    private function table()
    {
        // Get all users
        $users = User::has('roles')->with(['roles'])->get();
        if (!$users) {
            return response()->json(['message' => 'Users are not found.'], 404);
        }

        // Transform User
        $users = $users->map(function ($user) {
            return $this->transform($user);
        });

        // Return Users
        return $users;
    }

    // Archive User
    public function archive(String $user_id)
    {
        // Find User
        $user = User::find($user_id);
        // Check if user exist
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
        // Soft Delete User
        $user->delete();
        // Get User
        $users = $this->table();
        // Return
        return response()->json(['message' => 'User archived successfully.', 'data' => $users], 201);
    }

    // Archive Selected Users
    public function archiveUsers(Request $request)
    {
        // Validate the request to ensure `ids` is an array of integers
        $validated = $request->validate([
            'ids' => 'required',
            'ids.*' => 'integer', // Each item in the array should be an integer
        ]);

        // Find records based on the provided IDs
        $users = User::whereIn('id', $validated['ids']);

        // Soft delete the users records (archiving)
        if (!$users->exists()) {
            // Return error if no records found
            return response()->json(['error' => 'No users found with the provided IDs'], 404);
        }

        // Soft Deletes
        $users->delete();

        // Get Users
        $users = $this->table();

        // Return
        return response()->json(['message' => 'Users archived successfully', 'data' => $users], 201);
    }

    // Find a program
    public function findProgram($program_id)
    {
        $program = Program::find($program_id);
        // Check if program exist
        if (!$program) {
            return response()->json(['message' => 'Program not found.'], 404);
        }

        // Return program
        return $program;
    }

    // User Role Create Template
    public function userRoleCreate($user_id, $role_id)
    {

        // Find Role 
        $role = Role::find($role_id);
        // Check if that role does exist
        if (!$role) {
            return response()->json(['message' => 'Role not found.'], 404);
        }

        // Create User Role
        $user_role = UserRole::create([
            "user_id" => $user_id,
            "role_id" => $role_id,
        ]);
        if (!$user_role) {
            return response()->json(['Error upon role creation.'], 500);
        }

        // Return
        // return $user_role;
    }

    // User Creation Template
    public function userCreate($validatedCredentials)
    {

        // Create user
        $user = User::create([
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
        ]);
        // Check if the user is created
        if (!$user) {
            return response()->json(['Error upon user creation.'], 500);
        }
        // Return
        return $user;
    }

    // Update a user based on it's role
    public function update(AdminUserRequest $request, String $user_id)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();
        // Find User
        $user = User::find($user_id);
        // Check if User does exist
        if (!$user) {
            return response()->json(['message' => 'User not found.']);
        }

        // Update User
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

        // Save Changes
        $user->save();
        // Return
        return response()->json(['message' => 'User is updated.']);
    }

    // A function that finds college
    private function findCollege($college_id) {
        $college = College::find($college_id);
        if (!$college) {
            return response()->json(['message' => 'College not found.'], 404);
        }

        return $college;
    }

    public function store(AdminUserRequest $request)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Create User
        $user = $this->userCreate($validatedCredentials);

        // Create Roles
        foreach ($validatedCredentials['roles'] as $roleData) {
            $role = Role::find($roleData['id']);
            if (!$role) {
                return response()->json(['message' => 'Unable to create user because role does not exist.'], 404);
            }

            // Handle Specific role logic
            switch ($role['id']) {
                case 1: // Admin
                    return response()->json(['message' => 'Unable to create this type of user role.'], 400);
                case 2: // Chairperson
                    $program = $this->findProgram($validatedCredentials['program_id']);
                    if ($program['chairperson_id']) {
                        return response()->json(['message' => 'This program is already occupied.'], 400);
                    }

                    // Update Program
                    $program->chairperson_id = $validatedCredentials['id'];
                    // Save Changes
                    $program->save();
                    break;
                case 3: // Coordinator
                    $program = $this->findProgram($validatedCredentials['program_id']);
                    // Create Coordinator
                    Coordinator::create([
                        "id" => $user['id'],
                        "user_id" => $user['id'],
                        "program_id" => $program['id'],
                    ]);
                    break;
                case 4: // Company
                    // Create Company
                    Company::create([
                        'id' => $user['id'],
                        'user_id' => $user['id'],
                        'website_url' => $validatedCredentials['website_url'],
                        'name' => $validatedCredentials['company_name'],
                    ]);
                    break;
                case 5: // Dean
                    $college = $this->findCollege($validatedCredentials['college_id']);
                    if ($college['dean_id']) {
                        return response()->json(['message' => 'Unable to create new user because the college is already occupied.'], 400);
                    }

                    // Update college
                    $college->dean_id = $user['id'];
                    // Save Changes
                    $college->save();

                    break;

                case 6: // OSA
                    // Additional logic if needed
                    break;

                case 7: // Student
                    $program = $this->findProgram($validatedCredentials['program_id']);
                    $student = Student::create([
                        'id' => $user['id'],
                        'user_id' => $user['id'],
                        'program_id' => $program['id'],
                        'coordinator_id' => $validatedCredentials['coordinator_id'],
                        'age' => $validatedCredentials['age'],
                        'date_of_birth' => $validatedCredentials['date_of_birth'],
                    ]);
                    if (!$student) {
                        return response()->json(['message' => 'Unable to create student.'], 500);
                    }
                    break;

                case 8: // Supervisor
                    $office = $this->findOffice($validatedCredentials['office_id']);
                    if ($office['supervisor_id']) {
                        return response()->json(['message' => 'Office is already occupied by another supervisor.'], 400);
                    }
                    break;

                default:
                    return response()->json(['message' => 'Unknown user role.'], 400);
            }

             // Create User Role
             $this->userRoleCreate($user['id'], $role['id']);
        }

        return response()->json(['message' => 'User and roles created successfully.'], 201);
    }

    // Find Office by office_id
    public function findOffice($office_id) {
        $office = Office::find($office_id);

        if (!$office) {
            return response()->json(['message' => 'Office not found.'], 404);
        }

        return $office;
    }


    // Get all users
    public function index()
    {
        // Get Users
        $users = $this->table();

        // Return
        return response()->json($users, 200);
    }

    // Transform
    private function transform($user)
    {
        return [
            "id" => $user['id'],
            "roles" => $user['roles'],
            "first_name" => $user['first_name'],
            "middle_name" => $user['middle_name'],
            "last_name" => $user['last_name'],
            "gender" => $user['gender'],
            "phone_number" => $user['phone_number'],
            "email" => $user['email'],
            "email_verified_at" => $user['email_verified_at'],
            "street" => $user['street'],
            "barangay" => $user['barangay'],
            "city_municipality" => $user['city_municipality'],
            "province" => $user['province'],
            "postal_code" => $user['postal_code'],
            "created_at" => $user['created_at'],
            "updated_at" => $user['updated_at'],
            "deleted_at" => $user['deleted_at'],
        ];
    }
}
