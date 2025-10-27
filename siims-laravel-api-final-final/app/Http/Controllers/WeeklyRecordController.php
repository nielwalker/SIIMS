<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeeklyRecordRequest;
use App\Services\WeeklyRecordService;
use Illuminate\Http\Request;

class WeeklyRecordController extends Controller
{

    // Services
    private $weeklyRecordService;

    public function __construct(WeeklyRecordService $weeklyRecordService)
    {
        $this->weeklyRecordService = $weeklyRecordService;
    }

    /**
     * Summary of delete: Delete by ID.
     * @param string $week_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(string $week_id) {

        // Delete
        $this->weeklyRecordService->delete($week_id);
        
        // Return resposne
        return $this->jsonResponse(['message' => 'Record deleted.'], 200);

    }

    /**
     * Summary of update: Update by ID.
     * @param \App\Http\Requests\WeeklyRecordRequest $weeklyRecordRequest
     * @param string $week_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(WeeklyRecordRequest $weeklyRecordRequest, string $week_id)
    {

        // Get validated
        $validated = $weeklyRecordRequest->validated();

        // Add filters
        $filters = [
            'requestedBy' => $weeklyRecordRequest->query('requestedBy'),
        ];

        // Update
        $record = $this->weeklyRecordService->update($week_id,  $validated);

        // Return
        return $this->jsonResponse(['message' => 'Record updated.', 'data' => $record], 200);

    }

    /**
     * Summary of getByStudent: Get by student.
     * @param string $student_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByStudent(string $student_id) {

        // GET
        $records = $this->weeklyRecordService->getByStudent($student_id);

        // Return
        return $this->jsonResponse($records, 200);

    }

    /**
     * Summary of getByCoordinator: Get all weekly entries for a coordinator.
     * @param string $coordinator_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCoordinator(string $coordinator_id) {

        // Debug: Log the request
        \Log::info('Weekly entries request', [
            'coordinator_id' => $coordinator_id,
            'user_id' => auth()->id(),
            'user_roles' => auth()->user() ? auth()->user()->roles->pluck('name')->toArray() : []
        ]);

        // GET
        $records = $this->weeklyRecordService->getByCoordinator($coordinator_id);

        // Return
        return $this->jsonResponse($records, 200);

    }

    /**
     * Summary of get: Get the weekly records
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {

        // Add Filters
        $filters = [
            'requestedBy' => $request->query('requestedBy')
        ];

        // Get
        $records = $this->weeklyRecordService->get($filters);

        // Return
        return $this->jsonResponse($records, 200);
    }

    /**
     * Summary of create: Create new weekly record.
     * @param \App\Http\Requests\WeeklyRecordRequest $weeklyRecordRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(WeeklyRecordRequest $weeklyRecordRequest)
    {

        // Add Filters
        $filters = [
            'requestedBy' => $weeklyRecordRequest->query('requestedBy')
        ];

        // Get validated
        $validated = $weeklyRecordRequest->validated();

        // Create
        $record = $this->weeklyRecordService->create($validated, $filters);

        // Return
        return $this->jsonResponse(['message' => "New weekly entry added.", 'data' => $record], 201);
    }
}
