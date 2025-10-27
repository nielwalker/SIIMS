<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ActionLogger
{
    /**
     * Log an action with standardized attributes.
     *
     * @param string $actionType The type of action performed (e.g., Create, Update, Delete).
     * @param string $entity The entity affected by the action (e.g., Role, User).
     * @param int|null $entityId The ID of the affected entity, if applicable.
     * @param string $description A brief description of the event.
     * @param string $status The status of the event (e.g., Success, Failed).
     */
    public function logAction(string $actionType, string $entity, ?int $entityId, string $description, string $status, string $httpCode)
    {
        // Construct the log data
        $logData = [
            'timestamp' => now(),
            'user_id' => Auth::id(),
            'email' => Auth::user()->email ?? 'Guest',
            'user_role' => Auth::user() ? Auth::user()->roles->pluck('name')->toArray() : [],
            'action_type' => $actionType,
            'entity' => $entity,
            'entity_id' => $entityId,
            'description' => $description,
            'status' => $status,
            'http_code' => $httpCode,
            'ip_address' => request()->ip()
        ];

        // Log the event
        if ($status === 'Success') {
            Log::info("{$actionType} event on {$entity}", $logData);
        } else {
            Log::error("{$actionType} event on {$entity} failed", $logData);
        }
    }
}
