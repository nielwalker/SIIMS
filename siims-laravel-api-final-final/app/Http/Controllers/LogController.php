<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{
    // Define possible statuses based on HTTP codes
    private $statuses = [
        200 => 'Success',
        201 => 'Created',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * Summary of addNewLog: A public function that creates a record of log.
     * @param string $actionType
     * @param string $entity
     * @param string $entityID
     * @param string $description
     * @param int $http_code
     * @return void
     */
    public function addNewLog(String $actionType, String $entity, String $entityID, String $description, int $http_code)
    {
        // Get authenticated user
        $authUser = Auth::user();

        // Determine status based on HTTP code
        $status = $this->statuses[$http_code] ?? 'Unknown';

        Log::create([
            'user_id' => $authUser->id,
            'action_type' => $actionType,
            'name' => $this->getFullName(
                $authUser->first_name ?? "",
                $authUser->middle_name ?? "",
                $authUser->last_name ?? "",
            ),
            'entity' => $entity,
            'entity_id' => $entityID ?? "N/A",
            'description' => "User {$authUser->id} {$description}",
            'status' => $status,
            'http_code' => $http_code,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Summary of getAllLogs: A public function that gets all the logs.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllLogs()
    {
        // Get all Logs
        $logs = Log::orderBy('created_at', 'desc')->get();

        // Transform
        $transformedLogs = $logs->map(function ($log) {
            return [
                "id" => $log->id,
                "user_id" => $log->user_id,
                "action_type" => $log->action_type,
                "entity" => $log->entity,
                "entity_id" => $log->entity_id,
                "description" => $log->description,
                "status" => $log->status,
                "http_code" => $log->http_code,
                "ip_address" => $log->ip_address,
                "created_at" => $this->formatDateOnlyDate($log->created_at),
            ];
        });

        // Return response with status 200
        return response()->json($transformedLogs, 200);
    }
}
