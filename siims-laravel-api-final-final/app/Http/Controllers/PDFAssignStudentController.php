<?php

namespace App\Http\Controllers;

use App\Models\Student; // Assuming the Student model exists
use App\Imports\StudentNumbersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class PDFAssignStudentController extends Controller
{
    /**
     * Import classlist, update coordinator_id and phone_number, and handle missing students.
     */
    public function importClasslist(Request $request)
    {
        // Get authenticated user
        $authUser = Auth::user();

        // Validate the uploaded file
        $request->validate([
            'classlist' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('classlist');

        // Use the import class to process the file
        $import = new StudentNumbersImport();
        Excel::import($import, $file);

        // Filter out the "Student No" header
        $studentNumbers = array_filter($import->studentNumbers, function ($value) {
            return $value !== "Student No";
        });

        // Re-index the array to make it cleaner
        $studentNumbers = array_values($studentNumbers);

        // Initialize an array to store IDs of students not found in the system
        $notFound = [];

        foreach ($studentNumbers as $index => $studentNo) {
            // Find the student by their student number
            $student = Student::where('user_id', $studentNo)->first();

            if ($student) {
                // Update the coordinator_id
                $student->update(['coordinator_id' => $authUser->coordinator->id]);

                // Update the phone number if available
                if (isset($import->phoneNumbers[$index]) && !empty($import->phoneNumbers[$index])) {

                    $student->user->phone_number = $import->phoneNumbers[$index];
                    $student->user->save();

                    // $student->update(['phone_number' => $import->phoneNumbers[$index]]);
                }
            } else {
                // Add to the not found array
                $notFound[] = $studentNo;
            }
        }

        // Return the response
        return response()->json([
            'message' => "File processed successfully. " . count($studentNumbers),
            'updated_students' => count($studentNumbers) - count($notFound),
            'not_found_students' => $notFound,
        ], 201);
    }
}
