<?php

namespace App\Http\Controllers;

use App\Models\OfficeType;
use Illuminate\Http\Request;

class CompanyOfficeTypeController extends Controller
{
    // Get Office Types
    public function index() {
        // Get Office Types
        $officeTypes = OfficeType::all();


        // Return Office Types
        return response()->json($officeTypes, 200);
    }
}
