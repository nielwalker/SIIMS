<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminDeanRequest;
use App\Models\College;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class AdminDeanController extends Controller
{

    /**
     * Summary of transform: Attributes required for Dean View
     * @param mixed $dean
     * @return array
     */
    public function transform($dean)
    {
        return [
            'id' => $dean->id,
            'first_name' => $dean->first_name,
            'middle_name' => $dean->middle_name,
            'last_name' => $dean->last_name,
            'college_assigned' => $dean->college->name ?? "Not assigned.",
            'email' => $dean->email,
            'email_verified_at' => $dean->email_verified_at,
            'gender' => $dean->gender,
            'phone_number' => $dean->phone_number,
            'street' => $dean->street,
            'barangay' => $dean->barangay,
            'city_municipality' => $dean->city_municipality,
            'province' => $dean->province,
            'postal_code' => $dean->postal_code,
        ];
    }

    /**
     * Summary of table: Gets the required table for the deans
     * @return mixed|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    public function table()
    {
        $deans = User::with('college')->whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->get();

        // Check if dean exist
        if (!$deans) {
            return response()->json(['message' => 'Deans not found.'], 404);
        }

        // Transformed
        $deans = $deans->map(function ($dean) {
            return $this->transform($dean);
        });

        return $deans;
    }

    /**
     * Summary of findCollege: Finds a college by ID
     * @param string $college_id
     * @param string $method
     * @return mixed|TModel|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function findCollege(String $college_id, String $method = "post")
    {

        // Find College
        $college = College::find($college_id);
        // Check if college exist
        if (!$college) {

            // Method Message
            $message = "because the college is not found";

            switch ($method) {
                case "post":
                    $message = "Unable to create dean {$message}";
                    break;
                case "put":
                    $message = "Unable to edit dean {$message}";
                    break;
                case "delete":
                    $message = "Unable to delete dean {$message}";
                    break;
                default:
                    $message = "College not found.";
            }

            // Return status 404 if college not found
            return response()->json(['message' => $message], 404);
        }

        // Return college
        return $college;
    }

    /**
     * A public function that creates a new dean user
     * @param \App\Http\Requests\AdminDeanRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewDeanUser(AdminDeanRequest $request)
    {
        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find College
        $college = $this->findCollege(college_id: $validatedCredentials['college_id'], method: 'post');

        // Check if the College is assigned by another dean
        if ($college['dean_id']) {
            return response()->json(['message' => 'Unable to create new dean because the college is occupied by another dean.'], 400);
        }

        // Create a new User Instance
        $user = new User();

        // Fill the user attributes
        $user->fill(attributes: $validatedCredentials);

        // Save the user to the database
        $user->save();

        // Create User Role
        UserRole::create(attributes: [
            'user_id' => $user['id'],
            'role_id' => $this->DEAN_ROLE_ID
        ]);

        // Assign the Dean to the College
        $college->dean_id = $user['id'];

        // Save College
        $college->save();

        // Get All Deans
        $deans = $this->table();

        // Return status 201 successful create
        return response()->json(data: [
            'message' => 'The dean is created successfully.',
            'data' => $deans
        ], status: 201);
    }

    public function findDean(String $dean_id)
    {
        // Find User
        $dean = User::with('college')->whereHas('roles', function ($query) {
            $query->where('name', 'dean');
        })->find($dean_id);

        // Check if the dean exists
        if (!$dean) {
            return response()->json(['error' => 'Dean not found.'], 404);
        }

        // Return dean
        return $dean;
    }

    /**
     * A public function that updates a dean user.
     * @param \App\Http\Requests\AdminDeanRequest $request
     * @param string $dean_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateDeanUserById(AdminDeanRequest $request, String $dean_id)
    {

        // Get validated credentials
        $validatedCredentials = $request->validated();

        // Find the dean (user)
        $dean = $this->findDean(dean_id: $dean_id);

        // Find User
        $user = User::find($dean_id);

        // Mass update dean's personal details
        $user->update($validatedCredentials);

        // Handle college transfer
        $currentCollege = $dean->college;
        $newCollege = $this->findCollege(college_id: $request['college_id'], method:'put');

        // If the dean is currently assigned to a different college, remove them from the old college
        if ($currentCollege && $currentCollege->id !== $newCollege->id) {
            // Remove the dean from the current college
            $currentCollege->dean_id = null;
            $currentCollege->save();
        }

        // Check if the new college already has a dean
        if ($newCollege->dean_id && $newCollege->dean_id !== $dean->id) {
            return response()->json(data: ['error' => 'The college is already occupied by another dean.'], status: 400);
        }

        // Assign the dean to the new college
        $newCollege->dean_id = $dean->id;

        // Save college
        $newCollege->save();

        // Get Deans
        $deans = $this->table();

        // Send success message
        return response()->json(data: ['message' => 'The dean has been updated and transferred to the new college.', 'data' => $deans], status: 201);
    }

    // Get dean
    public function show(String $dean_id)
    {
        // Find Dean
        $dean = $this->findDean($dean_id);

        // TODO: Transformed Dean
        $dean = $this->transform($dean);

        // Return dean
        return response()->json($dean, 200);
    }

    /**
     * A public function that gets all the list dean users
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllDeans()
    {

        // Get all deans
        $deans = $this->table();

        // Return Dean data
        return response()->json($deans, 200);
    }

    // Archive selected dean/s
    public function archive(Request $request)
    {
        // Validate the request to ensure `ids` is an array of integers
        $validated = $request->validate([
            'ids' => 'required',
            'ids.*' => 'integer', // Each item in the array should be an integer
        ]);
        // Find records based on the provided IDs
        $deans = User::whereIn('id', $validated['ids']);

        // Soft delete the records (archiving)
        if ($deans->exists()) {
            $deans->delete(); // Soft Deletes
            return response()->json(['success' => 'Deans archived successfully'], 200);
        }

        // Return error if no records found
        return response()->json(['error' => 'No deans found with the provided IDs'], 404);
    }

    // Delete Dean
    public function destroy(String $dean_id)
    {
        // Find Dean
        $dean = User::find($dean_id);

        // Check if dean exist
        if (!$dean) {
            return response()->json(['message' => 'A Dean is not found']);
        }

        // Delete dean
        $dean->delete();

        // Return successful delete
        return response()->json([
            'success' => 'A dean has been deleted.'
        ]);
    }
}
