<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentDtrEntryRequest;
use App\Models\Application;
use App\Models\DtrEntry;
use App\Services\ActionLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentDtrEntryController extends Controller
{
    /**
     * Helper method to check if the application exists.
     */
    private function checkApplicationExists(String $application_id, ActionLogger $actionLogger)
    {
        $application = Application::find($application_id);

        if (!$application) {
            // Log failure
            $actionLogger->logAction(
                actionType: 'Read',
                entity: 'Application',
                entityId: $application_id,
                description: "Application ID {$application_id} not found.",
                status: 'Failed',
                httpCode: "404"
            );

            return response()->json(['message' => 'Application not found'], 404);
        }

        return $application;
    }

    /**
     * Helper method to validate time_in and time_out.
     */
    private function validateTimeInOut($timeIn, $timeOut, String $dtr_id, ActionLogger $actionLogger)
    {
        $timeIn = Carbon::parse($timeIn);
        $timeOut = Carbon::parse($timeOut);

        if ($timeOut->lt($timeIn)) {
            // Log failure
            $actionLogger->logAction(
                actionType: 'Update',
                entity: 'DtrEntry',
                entityId: $dtr_id,
                description: "Failed to update DTR entry. Time out ({$timeOut}) is earlier than time in ({$timeIn}).",
                status: 'Failed',
                httpCode: "422"
            );

            return response()->json(['message' => 'Time out must not be earlier than time in'], 422);
        }

        return [$timeIn, $timeOut];
    }

    /**
     * Helper method to log actions.
     */
    private function logAction(String $actionType, String $entity, $entityId, String $description, String $status, String $httpCode, ActionLogger $actionLogger)
    {
        $actionLogger->logAction(
            actionType: $actionType,
            entity: $entity,
            entityId: $entityId,
            description: $description,
            status: $status,
            httpCode: $httpCode
        );
    }

    /**
     * A public function that gets all the DTR entries based on the application_id
     */
    public function getAllDtrEntries(String $application_id, ActionLogger $actionLogger)
    {
        $dtrEntries = DtrEntry::where('application_id', $application_id)->get();

        if ($dtrEntries->isEmpty()) {
            $this->logAction('Read', 'DtrEntry', null, "No DTR entries found for Application ID {$application_id}.", 'Failed', '404', $actionLogger);

            return response()->json(['message' => 'No DTR entries found'], 404);
        }

        $this->logAction('Read', 'DtrEntry', null, "Successfully fetched DTR entries for Application ID {$application_id}.", 'Success', '200', $actionLogger);

        return response()->json([
            'message' => 'DTR entries fetched successfully',
            'dtr_entries' => $dtrEntries
        ], 200);
    }

    /**
     * A public function that deletes a specific DTR (Daily Time Record) entry by ID
     */
    public function deleteDtrEntryById(String $application_id, String $dtr_id, ActionLogger $actionLogger)
    {
        $dtrEntry = DtrEntry::where('application_id', $application_id)->where('id', $dtr_id)->first();

        if (!$dtrEntry) {
            $this->logAction('Delete', 'DtrEntry', $dtr_id, "Failed to delete DTR entry. DTR entry with ID {$dtr_id} not found for Application ID {$application_id}.", 'Failed', '404', $actionLogger);

            return response()->json(['message' => 'DTR entry not found'], 404);
        }

        $dtrEntry->delete();

        $this->logAction('Delete', 'DtrEntry', $dtr_id, "Successfully deleted DTR entry with ID {$dtr_id} for Application ID {$application_id}.", 'Success', '200', $actionLogger);

        $remainingDtrEntries = DtrEntry::where('application_id', $application_id)->get();

        return response()->json([
            'message' => 'DTR entry deleted successfully',
            'remaining_dtr_entries' => $remainingDtrEntries
        ], 200);
    }

    /**
     * A public function that updates a specific DTR (Daily Time Record) entry by ID
     */
    public function updateDtrEntryById(StudentDtrEntryRequest $request, String $application_id, String $dtr_id, ActionLogger $actionLogger)
    {
        $validatedCredentials = $request->validated();

        $application = $this->checkApplicationExists($application_id, $actionLogger);
        if (!$application) return $application;

        $dtrEntry = DtrEntry::where('application_id', $application_id)->find($dtr_id);

        if (!$dtrEntry) {
            $this->logAction('Update', 'DtrEntry', $dtr_id, "Failed to update DTR entry. DTR ID {$dtr_id} not found.", 'Failed', '404', $actionLogger);

            return response()->json(['message' => 'DTR entry not found'], 404);
        }

        list($timeIn, $timeOut) = $this->validateTimeInOut($validatedCredentials['time_in'], $validatedCredentials['time_out'], $dtr_id, $actionLogger);
        if (!$timeIn) return $timeIn;

        $dtrEntry->update([
            'date' => $validatedCredentials['date'],
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'hours_received' => $validatedCredentials['hours_received'],
        ]);

        $this->logAction('Update', 'DtrEntry', $dtrEntry->id, "Updated DTR entry ID {$dtrEntry->id} for Application ID {$application->id}.", 'Success', '200', $actionLogger);

        $studentDtrEntries = DtrEntry::where('application_id', $application->id)->get();

        return response()->json([
            'message' => 'DTR entry updated successfully',
            'dtr_entries' => $studentDtrEntries
        ], 200);
    }

    /**
     * A public function that creates a new DTR (Daily Time Record)
     */
    public function addNewDtrEntry(StudentDtrEntryRequest $request, String $application_id, ActionLogger $actionLogger)
    {
        $validatedCredentials = $request->validated();

        $application = $this->checkApplicationExists($application_id, $actionLogger);
        if (!$application) return $application;

        list($timeIn, $timeOut) = $this->validateTimeInOut($validatedCredentials['time_in'], $validatedCredentials['time_out'], 0, $actionLogger);
        if (!$timeIn) return $timeIn;

        $dtrEntry = DtrEntry::create([
            'application_id' => $application->id,
            'date' => $validatedCredentials['date'],
            'status_id' => 3,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'hours_received' => $validatedCredentials['hours_received'],
        ]);

        $this->logAction('Create', 'DtrEntry', $dtrEntry->id, "Added a new DTR entry for Application ID {$application->id}.", 'Success', '201', $actionLogger);

        $studentDtrEntries = DtrEntry::where('application_id', $application->id)->get();

        return response()->json([
            'message' => 'DTR entry added successfully',
            'dtr_entries' => $studentDtrEntries
        ], 201);
    }
}
