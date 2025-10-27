<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileRequest;
use App\Models\Coordinator;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * The student controller.
     */
    private $studentController;

    /**
     * The userRole controller.
     */
    private $userRoleController;

    /**
     * FileController constructor.
     */
    public function __construct(StudentController $studentController, UserRoleController $userRoleController)
    {
        $this->user = Auth::user(); // Initialize the authenticated user
        $this->studentController = $studentController;
        $this->userRoleController = $userRoleController;
    }

    /**
     * Summary of uploadVerifyStudents: A 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function uploadVerifyStudents(Request $request)
    {

        set_time_limit(300);  // Set to 5 minutes (in seconds)

        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx|max:2048',
        ]);
        

        $file = $request->file('file');
        $missingStudents = [];

        // Open the file for reading
        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $row = 0; // Row counter

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row++;

                // Skip the header row
                if ($row == 1) {
                    continue;
                }

                // Ensure the second column exists and is numeric
                if (isset($data[1]) && is_numeric($data[1])) {
                    $studentId = (int)$data[1];

                    // Check if the student exists in the database
                    if (!Student::find($studentId)) {
                        $missingStudents[] = $studentId;
                    }
                }
            }

            fclose($handle);
        }

        // Return a JSON response with missing student IDs
return response()->json([
    'message' => 'File processed successfully. Missing students: ' . implode(', ', $missingStudents),
    'missing_students' => $missingStudents,
], 201);

    }

    /**
     * Handle the uploaded CSV file, create users, and store students.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $program_id
     * @return \Illuminate\Http\Response
     */
    public function uploadStudents(Request $request, String $program_id)
    {

        set_time_limit(300);  // Set to 5 minutes (in seconds)

        // Validate that a file is uploaded
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        // Retrieve the uploaded file
        $file = $request->file('file');

        // Open the file for reading
        $fileHandle = fopen($file->getRealPath(), 'r');

        // Skip the header row
        fgetcsv($fileHandle);

        // Begin a transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Loop through each row in the CSV
            while (($row = fgetcsv($fileHandle)) !== false) {
                // Convert each column to UTF-8 encoding to prevent character issues
                $row = array_map(function ($value) {
                    return mb_convert_encoding($value, 'UTF-8', 'auto');
                }, $row);

                // Check if a user already exists by email (or student_no)
                $existingUser = User::where('email', $row[2])->orWhere('id', $row[0])->first();

                if ($existingUser) {
                    // User already exists, assign existing user to student
                    $user = $existingUser;
                } else {
                    // Create a new user if none exists
                    $user = User::create([
                        'id' => $row[0],  // Assuming student_no is unique and used as ID
                        'first_name' => $row[1],  // Assuming First Name is at index 1
                        'last_name'  => '',  // No last name in the CSV, so we leave it blank
                        'email'      => $row[2],  // Assuming Email is at index 2
                        'password'   => bcrypt('defaultpassword'),  // Set a default password (or handle it accordingly)
                        'is_admin'   => false,    // Set admin status as false (customize as needed)
                    ]);
                }

                // Store the student information, link to existing or newly created user role
                $this->userRoleController->updateOrCreate(user: $user, roleID: 7); // Student Role ID
                /* UserRole::updateOrCreate(
                    ['user_id' => $user->id, 'role_id' => 7],
                    [
                        'user_id' => $user->id,
                        "role_id" => 7,
                    ]
                ); */



                // Store the student information, link to existing or newly created user
                $this->studentController->updateOrCreate($user, $program_id);

                /* Student::updateOrCreate(
                    ['user_id' => $user->id, 'program_id' => $program_id, 'id' => $user->id],
                    [
                        "id" => $user->id,
                        'user_id' => $user->id,
                        'program_id' => $program_id,
                        'status_id' => 8,
                        // You can set additional fields like age, date_of_birth, etc. if you have them in the CSV
                        'coordinator_id' => null,  // Assuming you will assign coordinator_id later
                        'age' => null,  // If age is available in the CSV, assign it here
                        'date_of_birth' => null,  // If date_of_birth is available in the CSV, assign it here
                        // 'last_applied_at' => now(),  // Use current timestamp as an example
                    ]
                ); */
            }

            // Commit the transaction
            DB::commit();

            // Return a success response
            return response()->json(['message' => 'Students uploaded successfully'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            // Return the error message
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            // Close the file after reading
            fclose($fileHandle);
        }
    }

    /**
     * Handle the uploaded CSV file, create users, and store coordinators.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadCoordinators(Request $request, String $program_id)
    {
        // Validate that a file is uploaded
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        // Retrieve the uploaded file
        $file = $request->file('file');

        // Open the file for reading
        $fileHandle = fopen($file->getRealPath(), 'r');

        // Skip the header row
        fgetcsv($fileHandle);

        // Begin a transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Loop through each row in the CSV
            while (($row = fgetcsv($fileHandle)) !== false) {
                // Convert each column to UTF-8 encoding to prevent character issues
                $row = array_map(function ($value) {
                    return mb_convert_encoding($value, 'UTF-8', 'auto');
                }, $row);

                // Check if a user already exists by email (or Faculty_ID)
                $existingUser = User::where('email', $row[3])->orWhere('id', $row[0])->first();

                if ($existingUser) {
                    // User already exists, assign existing user to coordinator
                    $user = $existingUser;
                } else {
                    // Create a new user if none exists
                    $user = User::create([
                        'id' => $row[0],
                        'first_name' => $row[2],  // Assuming First Name is at index 2
                        'last_name'  => $row[1],  // Assuming Last Name is at index 1
                        'email'      => $row[3],  // Assuming Email is at index 3
                        'password'   => bcrypt('defaultpassword'),  // Set a default password (or handle it accordingly)
                        'is_admin'   => false,    // Set admin status as false (customize as needed)
                    ]);

                    // Store the student information, link to existing or newly created user role
                    UserRole::create(
                        [
                            'user_id' => $user->id,
                            "role_id" => 3,
                        ]
                    );
                }



                // Store the coordinator information, link to existing or newly created user
                Coordinator::updateOrCreate(
                    ['user_id' => $user->id, 'program_id' => $program_id, 'id' => $user->id],
                    ['user_id' => $user->id, 'program_id' => $program_id, 'id' => $user->id]
                );
            }

            // Commit the transaction
            DB::commit();

            // Return a success response
            return response()->json(['message' => 'Coordinators uploaded successfully'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            // Return the error message
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            // Close the file after reading
            fclose($fileHandle);
        }
    }
}
