<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChairpersonResource;
use App\Models\User;
use Illuminate\Http\Request;

class ChairpersonV2Controller extends Controller
{

    // Log Controller
    private $logController;

    public function __construct(LogController $logController)
    {
        $this->logController = $logController;
    }

    /**
     * Summary of restoreChairpersonByID: A public function that restores a chairperson by ID.
     * @param string $chairperson_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreChairpersonByID(String $chairperson_id) {

        // Restore user
        $user = $this->findAndRestoreModel($chairperson_id, User::class);

         // Add new log
         $this->logController->addNewLog(
            'Restores a chairperson',
            'Chairperson',
            $user->id,
            'attempts to restore a chairperson',
            '200',
        );

         // Return 
         return $this->jsonResponse([
            'message' => 'Chairperson Restored',
            'type' => 'restore',
        ], 201);

    }

    /**
     * Summary of getAllChairpersons: A public function that gets all list of chairpersons.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllChairpersons(Request $request)
    {

        // Define the status
        $status = (string) $request->input('status');

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('per_page', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        /**
         * 
         * Get all users (chairperson)
         * 
         * Conditions:
         *
         * - Get only soft deleted users (chairperson) if the status (request) is archived
         * - Get all users (chairperson) except for those soft deleted users (chairpersons).
         * 
         * 
         */
        $query = $status === 'archived' ? User::onlyTrashed()->with(['roles']) : User::with(['roles']);

        $query = $query->whereHas('roles', function ($query) {
            $query->where('name', 'chairperson');
        })->with(['program.college']);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%')->orWhere('id', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $chairpersons = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $chairpersonsResources = ChairpersonResource::collection($chairpersons);

        // Add new log
        $this->logController->addNewLog(
            'Views the list of chairpersons',
            'Chairperson',
            'N/A',
            'attempts to view the list of chairpersons.',
            '200',
        );

        // Get all Chairpersons
        return $chairpersonsResources;
    }
}
