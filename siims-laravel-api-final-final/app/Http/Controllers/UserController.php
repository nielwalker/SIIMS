<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected $user;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct()
    {
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    /**
     * Summary of findUser: A public function that finds the user by ID.
     * @param string $id
     * @return TModel|null
     */
    public function findUser(String $id) {

        // Find User by user_id
        $user = User::where('id', $id)->first();

        // Return user
        return $user;

    }

    /**
     * Summary of softDeleteUserById: A public function that soft deletes a User.
     * @param string $user_id
     * @return void
     */
    public function softDeleteUserById(String $user_id)
    {

        // Find User by user_id
        $user = User::find($user_id);

        // Delete User
        $user->delete();

        // Return response with status 201
        // return response()->json(['message' => 'User is deleted.'], 201);
    }

    /**
     * Summary of archiveUsers: A public function that deletes selected Users.
     * @param \App\Http\Requests\UserRequest $request
     * @return JsonResponse|mixed
     */
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

        // Get all users
        $users = $this->getAllTransformedUsers();

        // Return
        return response()->json(['message' => 'Users archived successfully', 'data' => $users], 201);
    }

    /**
     * Summary of getAllTransformedUsers: A private function that gets all transformed Users.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getAllTransformedUsers()
    {

        $users = User::with(['roles'])->where('id', '!=', '2021301502')->orderBy('created_at', 'desc')->get();

        // Transform User's attributes
        $transformedUsers = $users->map(function ($user) {
            return [
                "id" => $user->id,
                "roles" => $user->roles ? $user->roles->map(function ($role) {
                    return [
                        "name" => ucwords($role->name),
                    ];
                }) : "",
                "name" => $this->getFullName($user->first_name, $user->middle_name ?? "", $user->last_name),
                "email" => $user->email,
                "phone_number" => $user->phone_number,
                "created_at" => $this->formatDateOnlyDate($user->created_at),
                "updated_at" => $this->formatDateOnlyDate($user->updated_at),
                "deleted_at" => $this->formatDateOnlyDate($user->deleted_at),
            ];
        });

        // Return Transformed Users
        return $transformedUsers;
    }

    /**
     * Summary of fetchAllUsers: A protected function that gets all Users.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function fetchAllUsers() {

      

        $users = User::withTrashed()->with(['roles'])->orderBy('created_at', 'desc');

        return $users;

    }

    /**
     * Summary of getAllUsers: A public function that gets all Users.
     * @return JsonResponse|mixed
     */
    public function getAllUsers(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Get all Users
        $query = $this->fetchAllUsers();

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $users = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $usersResources = UserResource::collection($users);

        // Store a new log the database
        Log::create([
            'user_id' => $this->user->id,
            'action_type' => 'Views a list document types',
            'entity' => 'Document',
            'entity_id' => "N/A",
            'description' => "User {$this->user->id} views a list of document types.",
            'status' => 'Success',
            'http_code' => 200,
            'ip_address' => request()->ip(),
        ]);

        // Return Document Type Resources
        return $usersResources;
    }

    /**
     * Summary of addNewUser: A protected function that adds a New User
     * @param array $request
     * @return User
     */
    protected function addNewUser(array $validated): User {

        // Create User
        $user = User::create($validated);

        // Return User
        return $user;

    }
}
