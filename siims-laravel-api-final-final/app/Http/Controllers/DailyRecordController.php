<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyRecordRequest;
use App\Http\Resources\DailyRecordResource;
use App\Services\DailyRecordService;
use Illuminate\Http\Request;

class DailyRecordController extends Controller
{

    // Services
    private $dailyRecordService;

    public function __construct(DailyRecordService $dailyRecordService)
    {
        $this->dailyRecordService = $dailyRecordService;
    }

    /**
     * Summary of update: Update by ID.
     * @param \App\Http\Requests\DailyRecordRequest $dailyRecordRequest
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(DailyRecordRequest $dailyRecordRequest, string $id) {

         // Add Filters
         $filters = [
            'requestedBy' => $dailyRecordRequest->query('requestedBy')
        ];

        // Get validated
        $validated = $dailyRecordRequest->validated();

        // Update
        $record = $this->dailyRecordService->update($filters, $validated, $id);
        
        // Return
        return $this->jsonResponse(['message' => 'Record updated.', 'data' => $record], 200);

    }

    /**
     * Summary of delete: Delete by ID.
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(string $id) {

        // Delete
        $this->dailyRecordService->delete($id);

        // Return response
        return $this->jsonResponse(['message' => 'Record deleted'], 200);
    }

    /**
     * Summary of get: Get all records
     * @return \Illuminate\Http\JsonResponse
     */
    public function get() {

        // Fetch
        $dailyRecords = $this->dailyRecordService->get();

        // Return
        return $this->jsonResponse(DailyRecordResource::collection($dailyRecords), 200);

    }

    /**
     * Summary of create: Create a new record
     * @param \App\Http\Requests\DailyRecordRequest $dailyRecordRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(DailyRecordRequest $dailyRecordRequest)
    {

        // Add Filters
        $filters = [
            'requestedBy' => $dailyRecordRequest->query('requestedBy')
        ];

        // Get validated
        $validated = $dailyRecordRequest->validated();

        // Create daily time record
        $dailyRecord = $this->dailyRecordService->create($filters, $validated);

        // Return response
        return $this->jsonResponse(['message' => 'Created a new record', 'data' => $dailyRecord], 201);
    }
}
