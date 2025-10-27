<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeeklyEntryRequest;
use App\Http\Resources\WeeklyEntryResource;
use App\Models\WeeklyEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WeeklyEntryController extends Controller
{
    /**
     * Application Controller
     */
    private $applicationController;

    public function __construct(ApplicationController $applicationController)
    {
        $this->applicationController = $applicationController;
    }

    /**
     * Summary of findWeeklyEntry: A private function that finds a weekly entry that belongs to an application's ID and matches the ID.
     * @param string $weeklyEntryID - The ID of the weekly entry to find.
     * @param string $applicationID - The foreign ID of the weekly entry that belongs to an application.
     * @return WeeklyEntry
     */
    private function findWeeklyEntry(String $weeklyEntryID, String $applicationID): WeeklyEntry {

       

        // Find a specific weekly entry
        $weeklyEntry = WeeklyEntry::where('application_id', $applicationID)->where('id', $weeklyEntryID)->first();


        // Check if weekly entry does not exist
        if(!$weeklyEntry) {
            abort(404, 'Weekly Entry not found.');
        }

        // Return weekly entry
        return $weeklyEntry;

    }
    
    /**
     * Summary of getWeeklyEntries: A public function that finds all weekly entries that belongs to an application.
     * @param string $application_id - The foreign ID of the weekly entry that belongs to an application.
     * @return WeeklyEntry|array
     */
    private function getWeeklyEntries(String $application_id)
    {

        // Find all Weekly Entries where Application
        $weekly_entries = WeeklyEntry::where('application_id', $application_id);

        // Return Weekly Entries
        return $weekly_entries;
    }

    /**
     * Summary of deleteWeekEntryById: A public function that deletes the weekly entry by ID.
     * @param string $application_id - The foreign ID of the weekly entry that belongs from the application.
     * @param string $week_entry_id - The ID of the weekly entry to find and update.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function deleteWeekEntryById(String $application_id, String $week_entry_id) {

        // Find application by ID.
        $applicationID = $this->applicationController->findApplicationIdById($application_id);

        // Find a specific weekly accomplishment entry by ID.
        $weeklyEntry = $this->findWeeklyEntry($week_entry_id, $applicationID);

        // Delete weekly entry
        $weeklyEntry->delete();

        // Return response with message and status
        return $this->jsonResponse(['message' => 'Weekly Entry is deleted'], 201);

    }

    /**
     * Summary of updateWeekEntryById: A public function that updates the weekly entry by ID.
     * @param \App\Http\Requests\WeeklyEntryRequest $request
     * @param string $application_id - The foreign ID of the weekly entry that belongs from the application.
     * @param string $week_entry_id - The ID of the weekly entry to find and update.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateWeekEntryById(WeeklyEntryRequest $request, String $application_id, String $week_entry_id) {

        // Get validated
        $validated = $request->validated();
                
        // Find application by ID
        $applicationID = $this->applicationController->findApplicationIdById($application_id);

        // Find a specific weekly accomplishment entry by ID.
        $weeklyEntry = $this->findWeeklyEntry($week_entry_id, $applicationID);

        // Update and save weekly entry
        $weeklyEntry->update($validated);
        $weeklyEntry->save();

        // Return response with status, message and data.
        return $this->jsonResponse(
            [
                'message' => "Weekly Entry is updated.",
                "data" => new WeeklyEntryResource($weeklyEntry)
            ], 201
        );

    }

    /**
     * Summary of addNewWeekEntry: Adds a new weekly entry.
     * @param \App\Http\Requests\WeeklyEntryRequest $request
     * @param string $application_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewWeekEntry(WeeklyEntryRequest $request, String $application_id)
    {

        // Get validated
        $validated = $request->validated();

        // Find applicationID by ID
        $applicationID = $this->applicationController->findApplicationIdById($application_id);

        // Mass assign the Weekly Report
        $weeklyEntry = WeeklyEntry::create(array_merge(
            ["application_id" => $applicationID],
            $validated
        ));

        // Return status and the new weekly entry
        return $this->jsonResponse([
            'message' => "A new weekly entry is created.",
            'data' => new WeeklyEntryResource($weeklyEntry),
        ], 201);
    }

    /**
     * Summary of getAllWeeklyEntries: A public function that gets all weekly entries
     * @param \Illuminate\Http\Request $request
     * @param string $application_id - The foreign ID of weekly entry that belongs to an application.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllWeeklyEntries(Request $request, String $application_id)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        /**
         * Find applicationID by ID
         * 
         * - Includes the searching of application base on the role of the user.
         */
        $applicationID = $this->applicationController->findApplicationIdById($application_id);

        // Find all Weekly Entries where Application
        $query = $this->getWeeklyEntries($applicationID);

        // Paginate the results
        $weeklyEntries = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $weeklyEntriesResources = WeeklyEntryResource::collection($weeklyEntries);

        // Return Weekly Entry Resources
        return $weeklyEntriesResources;
        
    }

}
