<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCampusRequest;
use App\Models\Campus;
class AdminCampusController extends Controller
{
    // List of campuses
    public function index()
    {


        return Campus::all();
    }

    // Single Campus
    public function show(String $campus_id)
    {

        // Find Campus
        $campus = Campus::find($campus_id);

        // Check if the campus exists
        if (!$campus) {
            return response()->json(['message' => 'Campus not found'], 404);
        }

        // Return Campus
        return $campus;
    }

     // Store a Campus
     public function store(AdminCampusRequest $request)
     {
         // Get validated request
         $validatedCredentials = $request->validated();
 
         // Store campus record
         Campus::create($validatedCredentials);
 
         // Return successful create
         return response()->json([
             'success' => 'The campus has been created.'
         ]);
     }

       // Delete a Campus
    public function destroy(String $campus_id)
    {
        // Find Campus by Id
        $campus = Campus::find($campus_id);

        // Check if the campus exists
        if (!$campus) {
            return response()->json(['message' => 'Campus not found'], 404);
        }

        // Delete campus
        $campus->delete();

        // Return successful delete
        return response()->json([
            'success' => 'The campus has been deleted.'
        ]);
    }

    // Update a Campus
    public function update(AdminCampusRequest $request, String $campus_id)
    {
        // Get validated request
        $validatedCredentials = $request->validated();
        // Find the campus  by id
        $campus = Campus::find($campus_id);

        // Check if the campus exists
        if (!$campus) {
            return response()->json(['message' => 'Campus not found'], 404);
        }

        // Update the campus record
        $campus['name'] = $validatedCredentials['name'];

        // Save changes
        $campus->update();

        // Return Successful Update
        return response()->json([
            'success' => 'The campus has been updated.'
        ]);
    }
}
