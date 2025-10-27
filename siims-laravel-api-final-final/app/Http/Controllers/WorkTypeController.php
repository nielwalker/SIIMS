<?php

namespace App\Http\Controllers;

use App\Models\WorkType;
use Illuminate\Http\Request;

class WorkTypeController extends Controller
{

    public function getAllWorkTypes() {
        
        // Get all work types
        $work_types = WorkType::all();


        // Return work types
        return response()->json($work_types, 200);
    }
}
