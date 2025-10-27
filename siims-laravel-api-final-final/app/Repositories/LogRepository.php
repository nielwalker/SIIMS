<?php

namespace App\Repositories;

use App\Models\Log;
use App\Services\HelperService;
use Illuminate\Support\Facades\Auth;

class LogRepository implements LogRepositoryInterface
{


    private $helperService;

    public function __construct(HelperService $helperService) {
        $this->helperService = $helperService;
    }

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
     * Summary of create: A public function that creates a new logs.
     * @param string $entityID
     * @param string $model
     * @param string $http_code
     * @param string $actionType
     * @return void
     */
    public function create(String $entityID = "N/A", String $model, String $http_code, String $actionType) {
        
        // Get authenticated user
        $authUser = Auth::user();

        // Determine status based on HTTP code
        $status = $this->statuses[$http_code] ?? 'Unknown';

        Log::create([
            'user_id' => $authUser->id,
            'action_type' => $actionType,
            'name' => $this->helperService->getFullName($authUser->first_name, $authUser->middle_name, $authUser->last_name),
            'entity' => $model,
            'entity_id' => $entityID ?? "N/A",
            // 'description' => "User {$authUser->id} {$description}",
            'status' => $status,
            'http_code' => $http_code,
            'ip_address' => request()->ip(),
        ]);

    }
}
