<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeanRequest;
use App\Http\Resources\DeanResource;
use App\Models\College;
use App\Models\Log;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeanController extends UserController
{

    protected $user;
    protected $collegeController;

    /**
     * A user role controller
     */
    private $userRoleController;

    public function __construct(CollegeController $collegeController, Auth $auth, UserRoleController $userRoleController)
    {
        $this->user = $auth::user();
        $this->collegeController = $collegeController;
        $this->userRoleController = $userRoleController;
    }

    /**
     * Summary of findDean: A public function that finds a Dean User by ID
     * @param string $dean_id
     * @return User|\Illuminate\Database\Eloquent\Collection|null
     */
    public function findDean(String $dean_id)
    {

        $dean = User::with(['college'])->whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->find($dean_id);

          // Check if dean does not exist.
          if(!$dean) {
            abort(404, 'Dean not found.');
        }

        // Return dean
        return $dean;
    }

    /**
     * Summary of getAllDeansIncludingCollege: A public function that gets all Deans including their assigned College.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllDeansIncludingCollege()
    {

        $deans = User::whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->with('college')->get();


        // Transrom Dean's Attribute
        $transformedDeans = $deans->map(function ($dean) {
            return [
                "id" => $dean->id,
                "college" => $dean->college ? $dean->college->name : "No College assigned.",
                "first_name" => $dean->first_name,
                "middle_name" => $dean->middle_name,
                "last_name" => $dean->last_name,
                "email" => $dean->email,
                "email_verified_at" => $this->formatDate($dean->email_verified_at),
                "gender" => ucwords($dean->gender),
                "phone_number" => $dean->phone_number,
                "street" => $dean->street,
                "barangay" => $dean->barangay,
                "city_municipality" => $dean->city_municipality,
                "province" => $dean->province,
                "postal_code" => $dean->postal_code,
                "created_at" => $this->formatDate($dean->created_at),
                "updated_at" => $this->formatDate($dean->updated_at),
                "deleted_at" => $this->formatDate($dean->deleted_at),

            ];
        });

        // Return response with status 200
        return response()->json($transformedDeans, 200);
    }

    // Get info for dashboard
    public function dashboard()
    {

        // Get auth
        $user = Auth::user();
        // Get the college assigned
        $college = College::where('dean_id',  $user['id'])->first();

        // Return
        return response()->json([
            'college_name' => $college['name'],
            'first_name' => $user['first_name'],
            'middle_name' => $user['middle_name'],
            'last_name' => $user['last_name'],
        ], 200);
    }

    // Get info for profile
    public function profile()
    {
        // Get Auth User
        $user = Auth::user();
        // Get College
        $college = College::where('dean_id', $user['id'])->first();
        // Return
        return response()->json();
    }

    /**
     * Summary of getAllDeans: A public function that gets all Dean Lists
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllDeans(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('per_page', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Get All User (Dean) Info
        $query = $this->fetchAllUsers();

        // Get only user that has Role of Dean
        $query = $query->whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->with('college');

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
        $deans = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $deansResources = DeanResource::collection($deans);

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

        // Get All Deans
        return $deansResources;
    }

    /**
     * Summary of deleteDean: A public function that deletes a dean.
     * @param string $dean_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDean(String $dean_id)
    {

        // Find Dean by ID
        $user = $this->findDean($dean_id);

        // Delete Dean
        $user->delete();

        // Return response
        return $this->jsonResponse([
            'message' => 'Dean is deleted.'
        ], 201);
    }

    /**
     * Summary of updateDean: A public function that updates a Dean.
     * @param \App\Http\Requests\DeanRequest $request
     * @param string $dean_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDean(DeanRequest $request, String $dean_id)
    {

        // Get validated data
        $validated = $request->validated();

        // Find User (Dean)
        $user = $this->findDean($dean_id);

        /**
         * Check and update dean_id in the college
         */
        if ($validated['college_id']) {
            $this->collegeController->updateCollegeDeanID($user->id, $validated['college_id']);
        }

        // Update Dean
        $user->update($validated);
        $user->save();

        // Return response
        return $this->jsonResponse([
            'message' => 'A dean is updated',
            'data' => new DeanResource($user)
        ], 201);
    }

    /**
     * Summary of addNewDean: A public function that creates a new Dean User.
     * @param \App\Http\Requests\DeanRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewDean(DeanRequest $request)
    {

        // Get validated data
        $validated = $request->validated();

        // Create a new User
        $user = $this->addNewUser(validated: $validated);

        /**
         * After creating the user. Create a user roel for dean
         */
        $this->userRoleController->addUserRole($user->id, 5); // Dean

        /**
         * Check and update dean_id in the college
         */
        if ($validated['college_id']) {
            $this->collegeController->updateCollegeDeanID($user->id, $validated['college_id']);
        }

        // Find Dean
        $dean = $this->findDean($user->id);


        // Return Dean User Resource
        return $this->jsonResponse([
            'message' => 'A new dean is created.',
            'data' => new DeanResource($dean)
        ], 201);
    }
}
