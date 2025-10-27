<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Carbon\Carbon;
use Database\Seeders\BaseSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Basic CRUD Operations
 * 
 * - index: Retrieves a list of resources (e.g., getAllUsers)
 * - show: Displays a single resource (e.g., showUser)
 * - create: Displays a form for creating a new resource (e.g., createPost)
 * - store: Creates a new resource (e.g., storePost)
 * - edit: Displays a form for editing an existing resource (e.g., editProduct)
 * - update: Updates an existing resource (e.g., updateProduct)
 * - destroy: Deletes a resource (e.g., deleteUser)
 * 
 * Addtional Actions
 * 
 * - search: Retrieves resources based on search criteria (e.g., searchProducts)
 * - filter: Filters resources based on certain conditions (e.g., filterPosts)
 * - sort: Sorts resources based on specific fields (e.g., sortUsers)
 * - export: Exports data in a specific format (e.g., exportUsersToCsv)
 * - import: Imports data from a specific source (e.g., importProductsFromCsv)
 * - download: Downloads a file (e.g., downloadFile)
 * - upload: Uploads a file (e.g., uploadFile)
 * - process: Processes data (e.g., processPayment)
 * - validate: Validates input data (e.g., validateForm)
 * - authorize: Checks user authorization (e.g., authorizeAccess)
 */

abstract class Controller
{   

    // PREDEFINED CREDENTIALS
    protected $COMPANY_ID = 2023301502;

    // ROLE ID's
    protected $ADMIN_ROLE_ID = 1;
    protected $CHAIRPERSON_ROLE_ID = 2;
    protected $COORDINATOR_ROLE_DI = 3;
    protected $COMPANY_ROLE_ID = 4;
    protected $DEAN_ROLE_ID = 5;
    protected $OSA_ROLE_ID = 6;
    protected $STUDENT_ROLE_ID = 7;
    protected $SUPERVISOR_ROLE_ID = 8;

    /**
     * Helper method to return JSON responses.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonResponse($data, int $status = 200, array $headers = []): JsonResponse {
        return response()->json($data, $status, $headers);
    }

    /**
     * Summary of restoreModel: A protected function that finds and restoreModel
     * @param string $id
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return mixed
     */
    protected function findAndRestoreModel(String $id, string $modelClass) {

        // Find model by ID
        $model = $modelClass::withTrashed()->where('id', $id)->first();

        // Restore model
        $model->restore();

        // Return model
        return $model;
    }

    /**
     * Find a model by its ID or fail with a log message if not found.
     *
     * @param string $modelClass The model class to check.
     * @param mixed $id The ID of the model to find.
     * @param string $entity The name of the entity (e.g., 'College', 'User').
     * @param string|null $entity_id Optional entity ID to log in the failure message.
     * @param string $actionType The action description (e.g., 'Attempted to access').
     * @return mixed|\Illuminate\Http\JsonResponse The model if found, or a JSON response on failure.
     */
    protected function findModelOrFailAndLog(String $modelClass, String $id, String $entity, String $actionType, ?String $entity_id = null)
    {
        // Get Auth User
        $user = Auth::user();
      
        // Dynamically resolve the model instance
        $model = $modelClass::find($id);

        if (!$model) {

            // Log the failure
            Log::create([
                'user_id' => $user->id,
                'action_type' => $actionType,
                'entity' => $entity,
                'entity_id' => $entity_id ?: 'N/A',
                'description' => "User {$user->id} failed to find a {$entity} with ID {$id}.",
                'status' => 'Failed',
                'http_code' => 404,
                'ip_address' => request()->ip(),
            ]);

            // Return a 404 response with a failure message
            return $this->jsonResponse(['message' => "{$entity} not found."], 404);
        }

        return $model;
    }

    /**
     * Helper metod to get the search data, sanitize, and return.
     * @param \Illuminate\Support\Facades\Request $request
     * @return string
     */
    protected function sanitizeAndGet(Request $request) {
        return trim($request->input('search', ''));
    }

    /**
     * Summary of formatDate: Returns a Month Day Year date format
     * @param string $date
     * @return string
     */
    protected function formatDate($date) {
        return Carbon::parse($date)->format('F j, Y, g:i a');
    }

     /**
     * Summary of getFullName: Returns a Full name (Combination of first, middle, and last name)
     * @param string $first_name
     * @param string $middle_name
     * @param string $last_name
     * @return string
     */
    protected function getFullName(String $firstName = "", String $middleName="", String $lastName = "") {

        return trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
    }

    /**
     * Summary of formatDateOnlyDate: A protected function that returns only date
     * @param mixed $date
     * @return string
     */
    protected function formatDateOnlyDate($date) {
           // If the date is already a Carbon instance, format it
           if ($date instanceof Carbon) {
            return $date->format('F j, Y');  // e.g., "November 19, 2024"
        }
        
        // Otherwise, parse and format the date
        return Carbon::parse($date)->format('F j, Y');  // e.g., "November 19, 2024"
    }

    // Get user
    public function getAuthUser() {
        return Auth::user();
    }

    // Get data for sidebar
    public function sidebar() {
        // get authenticated user
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Concatenate full name
        $fullName = trim($user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name);

        // Get email
        $email = $user->email;

        // Return the response as JSON
        return response()->json([
            'full_name' => $fullName,
            'email' => $email
        ]);
    }
}
