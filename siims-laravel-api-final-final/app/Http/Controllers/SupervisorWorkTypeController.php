<?php

namespace App\Http\Controllers;

use App\Models\WorkType;
use Illuminate\Http\Request;

class SupervisorWorkTypeController extends WorkTypeController
{
    

    /**
     * Summary of index: Get all work types
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function index() {

        // Get all work types
        $work_types = WorkType::select('id', 'name')->get();

        // Return work types status 200
        return response()->json($work_types, 200);
    }
}
