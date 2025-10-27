<?php

namespace App\Http\Controllers;

use App\Http\Requests\DtrMarkStatusRequest;
use App\Http\Requests\DtrRequest;
use App\Http\Resources\DtrResource;
use App\Models\Application;
use App\Models\DtrEntry;
use App\Models\TimeRecordStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DtrController extends Controller
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
     * Summary of findDailyTimeRecordByID: A private function that finds and returns the Daily Time Record by ID.
     * @param string $dtr_id
     * @return DtrEntry|null
     */
    private function findDailyTimeRecordByID(String $dtr_id)
    {

        $dtr = DtrEntry::with(['status'])->find($dtr_id);

        return $dtr;
    }


    /**
     * Summary of findDailyTimeRecordsByApplicationID (Plural): A private function that gets all applications
     * @param string $application_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findDailyTimeRecordsByApplicationID(String $application_id)
    {
        // Get all Dtr Entries by Application ID
        $dtrEntries = DtrEntry::with(['status'])->where('application_id', $application_id)->orderBy('created_at', 'asc');

        return $dtrEntries;
    }


    /**
     * Summary of findAndTransformDailyTimeRecordByID: A private function that finds and transform Daily Tiem Record by ID.
     * @param string $dailyTimeRecordID
     * @return DtrResource
     */
    private function findAndTransformDailyTimeRecordByID(String $dailyTimeRecordID)
    {

        // Find Daily Time Record
        $dtrEntry = DtrEntry::with(['status'])->find($dailyTimeRecordID);
        // Transform and Return DTR Entry
        return new DtrResource($dtrEntry);
    }


    /**
     * Summary of markDtrById: A public function that marks the status of Daily Time Record by ID.
     * @param \App\Http\Requests\DtrMarkStatusRequest $request
     * @param string $dtr_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markDtrById(DtrMarkStatusRequest $request, String $dtr_id)
    {

        // Get validated
        $validated = $request->validated();

        // Find the DTR
        $dtr = $this->findDailyTimeRecordByID($dtr_id);

        // Check if the DTR entry exists
        if (!$dtr) {
            return $this->jsonResponse(['message' => 'DTR entry not found.'], 404);
        }

        // If status is not null, just update it
        $dtr->status_id = $validated['status_id'];
        // Save the updated DTR entry
        $dtr->save();

        // Return updated DTR
        return $this->jsonResponse(['message' => "A record is updated.", 'data' => new DtrResource($this->findDailyTimeRecordByID($dtr_id))], 201);
    }

    /**
     * Summary of getAllTimeRecords: A public function that gets all daily time records.
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllTimeRecords(Request $request, String $application_id) {
        // Get authenticated user
        $authUser = Auth::user();

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Find Application by ID
        $application = Application::where('student_id', $authUser->id)->find($application_id);

        // Fetch daily time records by ID of the latest application
        $query = $this->findDailyTimeRecordsByApplicationID($application->id);

        // Return Daily Time Record Entries after paginating and transforming
        return $this->paginateAndTransform($query, $perPage);
    }

    /**
     * Summary of getLatestDtrEntries: A public function that gets all Daily Time Record Entries base on the latest application.
     * Role Allowed: Student
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getLatestDtrEntries(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get latest application 'ID'
        $latestApplicationID = $this->applicationController->getLatestApplicationId();

        // Fetch daily time records by ID of the latest application
        $query = $this->findDailyTimeRecordsByApplicationID($latestApplicationID);

        // Return Daily Time Record Entries after paginating and transforming
        return $this->paginateAndTransform($query, $perPage);
    }

    /**
     * Summary of getDailyTimeRecordsByApplicationID: A public function that gets all Daily Time Record by Application ID.
     * @param \Illuminate\Http\Request $request
     * @param string $application_id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getDailyTimeRecordsByApplicationID(Request $request, String $application_id)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        $query = $this->findDailyTimeRecordsByApplicationID($application_id);

        // Return Daily Time Record Entries after paginating and transforming
        return $this->paginateAndTransform($query, $perPage);
    }




    /**
     * Summary of paginateAndTransform: A public function that paginate, transform and return Daily Time Record Entries resources.
     * @param \Illuminate\Contracts\Database\Eloquent\Builder $query
     * @param int $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    private function paginateAndTransform(Builder $query, int $perPage)
    {

        // Paginate the results
        $dailyTimeRecordEntries = $query->paginate($perPage);

        // Return Daily Time Record Entries
        return  DtrResource::collection($dailyTimeRecordEntries);
    }

    /**
     * Summary of deleteDtrById: A public function that deletes a Daily Time Record Entry by ID.
     * @param string $dtrId
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function deleteDtrById(String $dtrId)
    {

        // Find DTR
        $dtr = DtrEntry::find($dtrId);

        // Delete
        $dtr->delete();

        // Return response with status 201
        return $this->jsonResponse(['message' => 'Daily time record is deleted'], 201);
    }

    /**
     * Summary of updateDtrById: A public function that updates a new Daily Time Record.
     * @param \App\Http\Requests\DtrRequest $request
     * @param string $application_id
     * @param string $dtrId
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateDtrById(DtrRequest $request, String $dtrId)
    {

        // Get validated data
        $validated = $request->validated();

        // Find Dtr Entry by ID
        $dtr = DtrEntry::find($dtrId);

        if (!$dtr) {
            return response()->json(['message' => 'Daily Time Record not found.'], 404);
        }

        // Update Dtr
        $dtr->update($validated);
        $dtr->save();

        // Get transformed Daily Time Record
        $dtrEntries = $this->findAndTransformDailyTimeRecordByID($dtr->id);

        // Return response with status 201
        return $this->jsonResponse([
            'message' => 'Daily Time Record is updated.',
            'data' => $dtrEntries,
        ], 201);
    }

    /**
     * Summary of addNewDtr: A public function that adds a new record of Daily Time Record
     * @param \App\Http\Requests\DtrRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function addNewDtr(DtrRequest $request)
    {

        // Get validated data
        $validated = $request->validated();

        // Get latest application ID
        $latestApplicationID = $this->applicationController->getLatestApplicationId();

        // Mass assignment DTR Entry
        $dtr = DtrEntry::create(array_merge($validated, [
            'application_id' => $latestApplicationID,
        ]));

        // Get and transform Daily Time Records
        $dtrEntry = $this->findAndTransformDailyTimeRecordByID($dtr->id);

        // Return response with status 201
        return $this->jsonResponse([
            'message' => 'A daily time record is created.',
            'data' => $dtrEntry
        ], 201);
    }


    /**
     * Summary of getAllTransformedDtrEntries: A private function that gets all transformed Daily Time Record.
     * @param string $application_id
     * @return mixed|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    private function getAllTransformedDtrEntries(String $application_id)
    {
        // Get all Dtr Entries by Application ID
        $dtrEntries = DtrEntry::where('application_id', $application_id)->orderBy('created_at', 'asc')->get();

        // Check if Dtr Entries does exist
        if (!$dtrEntries) {
            return response()->json(['message' => 'Daily Time Record not found.'], 404);
        }

        // Transformed DTR attributes
        $transformedDtrEntries = $dtrEntries->map(function ($dtrEntry) {
            return [
                "id" => $dtrEntry->id,
                "date" => $dtrEntry->date,
                "time_in" => $dtrEntry->time_in,
                "time_out" => $dtrEntry->time_out,
                "hours_received" => $dtrEntry->hours_received,
            ];
        });

        // Return Dtr Entries
        return $transformedDtrEntries;
    }

    /**
     * Summary of getDtrByApplicationId: A public function that gets all DTR Entries by Application Id
     * @param string $application_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getDtrByApplicationId(String $application_id)
    {

        // Get all DTR Entries
        $dtrEntries = $this->getAllTransformedDtrEntries($application_id);

        // Return response with status 200
        return response()->json($dtrEntries, 200);
    }
}
