<?php

namespace App\Http\Controllers;

use App\Models\OfficeType;
use Illuminate\Http\Request;

class AdminOfficeTypeController extends Controller
{
    
    /**
     * Summary of index: Gets all types of offices
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function index() {
        // Get office types
        $office_types = OfficeType::all();

        // Return office types
        return response()->json($office_types);
    }
}
