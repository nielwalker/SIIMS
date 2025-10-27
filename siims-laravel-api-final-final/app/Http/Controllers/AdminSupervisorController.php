<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminSupervisorController extends Controller
{
    // Get all Supervisors
    /**
     * Get all Supervisors
     * Including their:
     * - Office
     * - Company
     * @return void
     */
    public function getAllSupervisors() {

        // Get all Supervisors
        $supervisors = User::whereHas('roles', function($query) {
            $query->where('name', 'supervisor');
        })->get();


        // Return supervisors
        return response()->json($supervisors);

    }

    // Get all supervisors with offices and company
    public function withOfficesAndCompany() {

        // Get all Supervisors with office and company
        // Get all Supervisors
        $supervisors = User::whereHas('roles', function($query) {
            $query->where('name', 'supervisor');
        })->with(['office.company'])->get();

        // Return 
        return response()->json($supervisors);

    }
}
