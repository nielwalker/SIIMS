<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssigningStudentRequest;
use App\Http\Requests\StudentImportRequest;
use App\Http\Requests\StudentRequest;
use App\Http\Resources\ActiveStudentResource;
use App\Http\Resources\SearchEndorseStudentResource;
use App\Http\Resources\StudentResource;
use App\Models\Application;
use App\Models\College;
use App\Models\DocumentSubmission;
use App\Models\Office;
use App\Models\Program;
use App\Models\Status;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends UserController
{

    /**
     * The coordinator controller
     */
    private $coordinatorController;

    /**
     * The user role controller
     */
    private $userRoleController;

    // Services
    private $studentService;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct(CoordinatorController $coordinatorController, UserRoleController $userRoleController, StudentService $studentService)
    {
        $this->coordinatorController = $coordinatorController;
        $this->userRoleController = $userRoleController;
        $this->studentService = $studentService;
    }

    /**
     * Summary of fetchAllStudents: A private function that fetch all students in a query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function fetchAllStudents(String $requestedBy = "")
    {

        // Get authenticated user
        $user = Auth::user();

        // Initialize query variable
        $query = null;

        // dd('testing');

        // ! FOR ADMIN
        if ($user->hasRole('admin')) {
            // Get all Students
            $query = Student::with(['user', 'program.college', 'status', 'studentStatus', 'coordinator.user', 'company', 'latestApplication.workPost.office.company', 'workExperiences'])->withCount(['applications', 'endorsementLetterRequests']);
        }

        // ! FOR COORDINATOR
        else if ($user->hasRole('coordinator') && $requestedBy === 'coordinator') {

            // Get all Students
            $query = Student::with(['user', 'program', 'studentStatus', 'company', 'latestApplication.workPost.office.company', 'workExperiences'])->where('coordinator_id', $user->id);
        }

        // ! FOR DEAN
        else if ($user->hasRole('dean') && $requestedBy === 'dean') {
            // Get all Students
            $query = Student::with(['user', 'program', 'status', 'coordinator.user', 'company', 'latestApplication.workPost.office.company', 'workExperiences'])->withCount(['applications', 'endorsementLetterRequests'])->whereHas('program.college', function ($query) use ($user) {
                $query->where('dean_id', $user->id);
            });
        }

        // ! FOR CHAIRPERSON
        else if (($user->hasRole('chairperson') && $requestedBy === 'chairperson') || ($user->hasRole('chairperson') && $requestedBy === 'admin')) {

            // Get all Students
            // Treat chairperson like admin: can see all students
            $query = Student::with(['user', 'program.college', 'status', 'studentStatus', 'coordinator.user', 'company', 'latestApplication.workPost.office.company', 'workExperiences'])->withCount(['applications', 'endorsementLetterRequests']);
        }

        // ! FOR COMPANY
        else if ($user->hasRole('company')) {
            // Get all Students
            $query = Student::with(['user', 'program', 'status', 'coordinator.user', 'company', 'latestApplication.workPost.office.company', 'workExperiences']);
        }

        // ! FOR SUPERVISOR
        else if ($user->hasRole('supervisor')) {
            $query = Student::with(['user', 'program', 'status', 'coordinator.user', 'company', 'latestApplication.workPost.office.company', 'workExperiences']);
        }

        // Return query
        return $query;
    }

    public function import(StudentImportRequest $studentImportRequest)
    {
        // Get validated data
        $validated = $studentImportRequest->validated();
        $file = $validated['file'];

        // Open and parse the CSV file
        $students = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $rowIndex = 0;

            // Loop through the file line by line
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowIndex++;

                // Skip rows until we reach index 15
                if ($rowIndex < 15) {
                    continue;
                }

                // Process row 15 (assuming "Student No" is at index 3)
                if ($rowIndex === 15) {
                    if (isset($row[3]) && strtolower(trim($row[3])) === 'student no') {
                        // Confirm the correct column header
                        continue;
                    } else {
                        return response()->json(['error' => 'Invalid file format. Column "Student No" not found.'], 400);
                    }
                }

                // Extract "Student No" and other attributes (e.g., Full Name) from subsequent rows
                if (isset($row[3]) && is_numeric($row[3])) {
                    // Assuming Full Name is at index 4 and another attribute is at index 5
                    // $fullName = isset($row[4]) ? trim($row[4]) : 'N/A';  // Adjust index as necessary
                    // $otherAttribute = isset($row[5]) ? trim($row[5]) : 'N/A';  // Adjust index as necessary

                    $fullName = isset($row[4]) && !empty(trim($row[4])) ? trim($row[4]) : (isset($row[5]) ? trim($row[5]) : 'N/A');
                    $email = isset($row[18]) && !empty(trim($row[18])) ? trim($row[18]) : (isset($row[20]) ? trim($row[20]) : 'N/A');
                    $phoneNumbers = isset($row[21]) && !empty(trim($row[21])) ? trim($row[21]) : (isset($row[23]) ? trim($row[23]) : 'N/A');

                    $students[] = [
                        'student_no' => $row[3],
                        'full_name'  => $fullName,
                        'email' => $email,
                        'phone_number' => $phoneNumbers,
                        //'other_attribute' => $otherAttribute,  // Add other attributes as needed
                    ];
                }
            }

            fclose($handle);
        }

        // Process the extracted student numbers and names
        if (empty($students)) {
            return response()->json(['error' => 'No student numbers found in the file.'], 400);
        }

        // Example: Store the student data or perform further processing
        foreach ($students as $student) {
            // Example: Insert into the database

            $user = User::create([
                'id' => $student['student_no'],
                'first_name' =>  $student['full_name'],
                'email' =>  $student['email'],
                'phone_number' =>  $student['phone_number'],
                'password' => bcrypt('password'),
            ]);

            Student::create([
                'id' => $user->id,
                'user_id' => $user->id,
                'coordinator_id' => $validated['coordinator_id'],
                'program_id' => $validated['program_id'],
               
            ]);

            // Create User Role
            UserRole::create([
                'user_id' => $user->id,
                'role_id' => 7,
            ]);

            // Student::create(['student_no' => $student['student_no'], 'full_name' => $student['full_name']]);
        }

        return response()->json([
            'message' => 'Import successful',
            'students' => $students,
        ], 201);
    }



    /**
     * Summary of markAsReadyForDeployment: A public function that marks the students status to ready for deployment.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function markAsReadyForDeployment(Request $request)
    {
        // Validate the request to ensure 'ids' is provided and is an array
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:students,id', // Ensure each ID exists in the students table
        ]);

        // Get the list of IDs from the request
        $ids = $validated['ids'];

        // Process each student ID
        foreach ($ids as $id) {
            // Find the student by ID
            $student = Student::find($id);

            // If student exists, update their status
            if ($student) {
                // Update student's status
                $student->student_status_id = 5; // Status: Ready For Deployment
                $student->save(); // Save the changes

                // Fetch the student's latest application
                $latestApplication = $student->latestApplication;



                // If latest application exists, update its status
                if ($latestApplication) {

                    $latestApplication->application_status_id = 10; // Update the application status
                    $latestApplication->save(); // Save the changes
                }
            }
        }

        return response()->json([
            'message' => 'Students are now active.',
        ], 201);
    }

    /**
     * Summary of updateOrCreate: A public function that updates or creates a Student.
     * @param \App\Models\User $user
     * @param string $programID
     * @return void
     */
    public function updateOrCreate(User $user, String $programID)
    {

        // Store the student information, link to existing or newly created user
        Student::updateOrCreate(
            ['user_id' => $user->id, 'program_id' => $programID, 'id' => $user->id, 'student_status_id' => 1],
            [
                "id" => $user->id,
                'user_id' => $user->id,
                'program_id' => $programID,
                'status_id' => 8,
                // You can set additional fields like age, date_of_birth, etc. if you have them in the CSV
                'coordinator_id' => null,  // Assuming you will assign coordinator_id later
                'student_status_id' => 1, // Not Yet Applied Status ID
                'age' => null,  // If age is available in the CSV, assign it here
                'date_of_birth' => null,  // If date_of_birth is available in the CSV, assign it here
                // 'last_applied_at' => now(),  // Use current timestamp as an example
            ]
        );
    }

    /**
     * Summary of searchStudent: A public function that searches a student by query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchStudent(Request $request)
    {
        $query = $request->input('query');

        $students = Student::whereHas('user', function ($q) use ($query) {
            $q->where('first_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('middle_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('last_name', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('email', 'LIKE', '%' . strtolower($query) . '%')
                ->orWhere('id', 'LIKE', '%' . $query . '%'); // Missing semicolon added here
        })->get();


        // Transform to resources
        $studentResources = SearchEndorseStudentResource::collection($students);

        // Return list of students
        return $this->jsonResponse($studentResources, 200);
    }

    /**
     * Summary of findStudent: A public function that finds a student by ID.
     * @param string $studentID
     * @return TModel|null
     */
    public function findStudent(String $studentID)
    {
        // Always use a direct, role-agnostic query to avoid null query issues
        $student = Student::with([
            'user',
            'program.college',
            'status',
            'studentStatus',
            'coordinator.user',
        ])->find($studentID);

        return $student;
    }

    public function getCompletedStudents(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Define the requested role by role
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all students  that has student status 1 (Not Yet Applied)
        $query = $this->fetchAllStudents($requestedBy)->where('student_status_id', 6)->with(['latestApplication']); // Completed

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $students = $query->paginate($perPage);

        $studentsResources = StudentResource::collection($students);

        // Return resources
        return $studentsResources;
    }

    /**
     * Summary of getActiveStudents: A public function that gets all active students.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getActiveStudents(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all students  that has student status 1 (Not Yet Applied)
        $query = $this->fetchAllStudents($requestedBy)->where('student_status_id', 5)->with(['latestApplication']); // Pending Approval

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $students = $query->paginate($perPage);

        $studentsResources = StudentResource::collection($students);

        // Return resources
        return $studentsResources;
    }

    /**
     * Summary of getReadyForDeploymentStudents: A public function that gets the students that are ready for deployment.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getReadyForDeploymentStudents(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all students  that has student status 1 (Not Yet Applied)
        $query = $this->fetchAllStudents($requestedBy)->where('student_status_id', 4)->with(['latestApplication']); // Pending Approval

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $students = $query->paginate($perPage);

        $studentsResources = StudentResource::collection($students);

        // Return resources
        return $studentsResources;
    }

    /**
     * Summary of getPendingApprovalStudents: A public function that gets the students that are pending approval.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getPendingApprovalStudents(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all students  that has student status 1 (Not Yet Applied)
        $query = $this->fetchAllStudents($requestedBy)->where('student_status_id', 2)->with(['latestApplication']); // Pending Approval

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $students = $query->paginate($perPage);

        $studentsResources = StudentResource::collection($students);

        // Return resources
        return $studentsResources;
    }
    /**
     * Summary of getEnrolledStudents: A public function that gets the enrolled students.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getEnrolledStudents(Request $request)
    {
        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all students  that has student status 1 (Not Yet Applied)
        $query = $this->fetchAllStudents($requestedBy)->where('student_status_id', 3); // Enrolled

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $students = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $studentsResources = StudentResource::collection($students);

        // Return resources
        return $studentsResources;
    }

    /**
     * Summary of getAllNotYetAppliedStudents: A public function that gets all students that is not yet applied.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllStudentsNotYetAppliedStudents(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Fetch all students  that has student status 1 (Not Yet Applied)
        $query = $this->fetchAllStudents($requestedBy)->where('student_status_id', 1);

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }

        // Paginate the results
        $students = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $studentsResources = StudentResource::collection($students);

        // Return resources
        return $studentsResources;
    }

    /**
     * Summary of getAllStudents: A public function that gets all the students
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllStudents(Request $request)
    {

        // Add filters
        $filters = [
            'perPage' => (int) $request->input('perPage', 5),
            'requestedBy' => (string) $request->input('requestedBy'),
            'searchTerm' => $this->sanitizeAndGet($request),
            'section' => (string) $request->input('section')
        ];

        // Get students
        $students = $this->studentService->get($filters);

        // Transform the paginated data into a resource collection
        $studentsResources = StudentResource::collection($students);

        // Return resources
        return $studentsResources;
    }

    /**
     * Summary of getAllActiveStudents: A public function that gets the all the active students.
     * 
     * - Coordinator: Gets all of coordinator's active students
     * - Supervisor: Gets all of office's active students.
     * 
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllActiveStudents(Request $request)
    {
        // Get authenticated supervisor
        $user = Auth::user();

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Declare 'query' variable
        $query = null;

        // ! For Company
        if ($user->hasRole('company')) {

            // Get Company
            $company = $user->company;

            // Find all active students associated the coordinatorthat has student status of active
            $query = Student::with(['user', 'studentStatus', 'applications', 'program.college'])->whereHas('applications.workPost.office.company', function ($query) use ($company) {
                $query->where('id', $company->id);
            })->where('student_status_id', 4);
        }

        // ! For Coordinator
        else if ($user->hasRole('coordinator')) {

            // Find all active students associated with the coordinator that has student status of active
            $query = Student::with(['user', 'studentStatus', 'latestApplication'])->where('coordinator_id', $user->coordinator->id);
        }

        // ! FOR Supervisor
        else if ($user->hasRole('supervisor')) {
            // Find supervisor's office
            $office = Office::where('supervisor_id', $user->id)->first();

            if (!$office) {
                return response()->json(['message' => 'Office not found.'], 404);
            }

            // Find all active students associated with the office's work posts
            $query = Student::with(['user', 'program.college', 'latestApplication.workPost.office.company.user', 'studentStatus'])
                ->whereHas('latestApplication.workPost.office', function ($query) use ($office) {
                    $query->where('id', $office->id); // Ensure the office matches the supervisor's office
                })
                ->where('student_status_id', 4); // Filter students with active status

        }

        // return $query->get();

        // Apply the search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('last_name', 'LIKE', '%' . strtolower($searchTerm) . '%')
                    ->orWhere('email', 'LIKE', '%' . strtolower($searchTerm) . '%')->orWhere('id', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Paginate the results
        $activeStudents = $query->paginate($perPage);

        // Transform the paginated data into a resource collection
        $activeStudentsResources = ActiveStudentResource::collection($activeStudents);

        // Return Active Students Resources
        return $activeStudentsResources;
    }


    /**
     * Summary of homeFetchJobs: A public function that gets all jobs list for Student's Homepage
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function homeFetchJobs(Request $request)
    {

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('per_page', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Get authenticated user
        $user = Auth::user();

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('per_page', 5);

        // Get and sanitize the search term
        $searchTerm = $this->sanitizeAndGet($request);

        // Find the most recent application the student made
        $latestApplication = $user->student->applications()->latest()->first();

        /**
         * If an application exists, return the associated WorkPost (the job)
         * Else, return the list of work posts
         */
        if (!$latestApplication) {
            // Get All Work Posts Available
            $workPosts = $this->workPostController->getAllWorkPosts($request, $perPage, $searchTerm);

            // Return Work Posts
            return $workPosts;
        }

        // Return response 
        return $this->jsonResponse($latestApplication, 200);
    }

    /**
     * Summary of home: A function that gets all data needed for the Student HomePage
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /* public function home()
    {

        // Get authenticated user
        $user = Auth::user();

        // Find and get Student
        $user = User::with(['student', 'student.workExperiences', 'student.educations'])->find($user->id);

        // Find the most recent application the student made
        $latestApplication = $user->student->applications()->latest();

        /// return $latestApplication->with(['workPost'])->first();

        // Return resources
        return $this->jsonResponse([
            "work_experiences" => $user->student->work_experiences,
            "educations" => $user->student->educations,
            "student" => $user,
            "studentStatus" => $user->student->status_id,
            "currently_applied_work_post" => $latestApplication->with(['workPost', 'student']) ? $latestApplication->with(['workPost'])->first() : null
        ], 200);
    } */


    public function getAllMyLatestDocuments()
    {
        // Get latest Application's Documents
        $application = Application::where('student_id', $this->user->id)->latest()->first();

        // Check if you have an application
        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        // Get Document Submissions with non-null file_path
        $documentSubmissions = DocumentSubmission::where('application_id', $application->id)
            ->whereNotNull('file_path') // Ensure file_path is not null
            ->with(['documentType'])
            ->get();

        // Transform Document Submissions attributes
        $transformedDocumentSubmissions = $documentSubmissions->map(function ($documentSubmission) {
            return [
                "id" => $documentSubmission->id,
                "name" => $documentSubmission->documentType->name,
                "file_path" => $documentSubmission->file_path,
            ];
        });

        // Return response with status 200
        return response()->json($transformedDocumentSubmissions, 200);
    }



    /**
     * Summary of assignToCoordinator: A public function that assigns the student to the coordinators.
     * @param \App\Http\Requests\AssigningStudentRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function assignToCoordinator(AssigningStudentRequest $request)
    {

        // Get validated
        $validated = $request->validated();

        // Loop Student by assigning them
        foreach ($validated['ids'] as $student_id) {

            $student = Student::find($student_id);

            // Update student coordinator id
            $student->coordinator_id = $validated['coordinator_id'];
            $student->save();
        }

        // Return response with status 201
        return response()->json(['message' => 'Students are assigned']);
    }

    /**
     * Summary of getAllStudentsByDean: A public function that gets all Students from Dean.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAllStudentsByDean()
    {

        // Get College
        $college = College::where('dean_id', $this->user->id)->first();

        //Check if college does exist
        if (!$college) {
            return response()->json(['message' => 'College not found.'], 404);
        }

        // Get all programs and pluck their ID's
        $programs = Program::where('college_id', $college->id)->pluck('id');

        // Get all student by program
        $students = Student::with(['user', 'program'])->whereIn('program_id', $programs)->get();

        // Transform Student
        $transformedStudents = $students->map(function ($student) {
            return $this->transform($student);
        });

        // Return responses with status 200
        return response()->json($transformedStudents, 200);
    }

    /**
     * Check if Student Can Apply
     */
    public function checkIfStudentCanApply($studentId)
    {
        $student = Student::findOrFail($studentId);

        // Check if student is blocked
        if ($student->blocked_until && now()->lessThan($student->blocked_until)) {
            return response()->json([
                'can_apply' => false,
                'message' => 'You cannot apply yet. Please wait until ' . $student->blocked_until->toDateTimeString(),
                'blocked_until' => $student->blocked_until
            ], 409);
        }

        return response()->json([
            'can_apply' => true
        ]);
    }

    /**
     * A public function that allows to update Student's status.
     */
    public function updateStudentStatus(Student $student, int $status_id)
    {

        $student->status_id = $status_id;
        $student->save();
    }

    /**
     * Summary of getStudentStatusId: A public function where to get the Status of the Student.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getStudentStatusId()
    {

        // Get auth User
        $user = Auth::user()->student;

        // ! DO NOT TOUCH (Open)
        // Get the student status only
        // $status = Student::with(['status'])->find($user->id);

        // Return response with status 200
        // return response()->json($status->status->id, 200);
        // ! DO NOT TOUCH (Close)

        // * Added at December 24, 2024.
        return $this->jsonResponse($user->student_status_id, 200);
    }

    /**
     * Summary of addNewStudent: A public function that adds new student.
     * @param \App\Http\Requests\StudentRequest $studentRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewStudent(StudentRequest $studentRequest)
    {

        // Get validated
        $validated = $studentRequest->validated();

        // Declare empty variable
        $coordinator = null;

        // Check if the validated data has a coordinator_id value
        if (isset($validated['coordinator_id'])) {

            // Find Coordinator base on the validated program_id
            $coordinator = $this->coordinatorController->getCoordinatorByProgramId($validated['program_id'], $validated['coordinator_id']);

            // Check if coordinator does not exist in the program
            if (!$coordinator) {
                return $this->jsonResponse([
                    'message' => 'Coordinator not found. Unable to create a student.'
                ], 404);
            }
        }

        // Create new user
        $user = $this->addNewUser($validated);

        // Create student
        $student = Student::create([
            "id" => $user->id,
            "user_id" => $user->id,
            "program_id" => $validated['program_id'],
            "coordinator_id" => $coordinator->id ?? null,
            // Persist optional direct company assignment if provided
            "company_id" => $validated['company_id'] ?? null,
            "age" => $validated['age'],
            "date_of_birth" => $validated['date_of_birth'],
        ]);

        // Create user role
        $this->userRoleController->addUserRole($student->id, 7); // Student Role ID

        // Find Student by ID
        $student = $this->findStudent($user->id);

        // Return create resource
        return $this->jsonResponse([
            'message' => 'A student is created.',
            'data' => new StudentResource($student)
        ], 201);
    }

    /**
     * Summary of deleteStudentByID: A public function that deletes a student by ID.
     * @param string $student_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteStudentByID(String $student_id)
    {

        // Find Student by ID
        $student = $this->findStudent($student_id);

        // Delete student
        $student->delete();

        // Return message and status
        return $this->jsonResponse([
            "message" => 'A student is deleted'
        ], 201);
    }

    /**
     * Summary of updateStudentByID: A public function that updates a student by ID.
     * @param \App\Http\Requests\StudentRequest $studentRequest
     * @param string $student_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStudentByID(StudentRequest $studentRequest, String $student_id)
    {

        // Get validated 
        $validated = $studentRequest->validated();

        // Find Student by ID
        $student = $this->findStudent($student_id);

        // Update Student and User
        $student->update($validated);
        $student->user->update($validated);
        $student->save();

        // Return message, data, and status
        return $this->jsonResponse([
            'message' => 'A student is updated',
            'data' => new StudentResource($this->findStudent($student_id)),
        ], 201);
    }


    /**
     * Summary of transform: A private function that gets transforms the student attributes.
     * @param \App\Models\Student $student
     * @return array
     */
    private function transform(Student $student, int $option = 1)
    {
        // Get company name from latest application
        $companyName = "—";
        
        try {
            // Debug logging to see what data is available
            \Log::info("=== Student ID: {$student->id} ===");
            \Log::info("Has direct company: " . ($student->company ? 'Yes' : 'No'));
            if ($student->company) {
                \Log::info("Direct company name: " . $student->company->name);
            }
            
            \Log::info("Has latestApplication: " . ($student->latestApplication ? 'Yes' : 'No'));
            \Log::info("Has workExperiences: " . ($student->workExperiences ? 'Yes' : 'No'));
            
            // First priority: Direct company assignment
            if ($student->company) {
                $companyName = $student->company->name;
                \Log::info("Using direct company assignment: " . $companyName);
            }
            // Second priority: Latest application
            else if ($student->latestApplication && 
                $student->latestApplication->workPost && 
                $student->latestApplication->workPost->office && 
                $student->latestApplication->workPost->office->company) {
                $companyName = $student->latestApplication->workPost->office->company->name;
                \Log::info("Using company from application: " . $companyName);
            }
            // Third priority: Work experiences
            else if ($student->workExperiences && $student->workExperiences->count() > 0) {
                $latestWorkExperience = $student->workExperiences->sortByDesc('created_at')->first();
                if ($latestWorkExperience && $latestWorkExperience->company_name) {
                    $companyName = $latestWorkExperience->company_name;
                    \Log::info("Using company from work experience: " . $companyName);
                }
            }
            
            \Log::info("Final company name: " . $companyName);
            \Log::info("=== End Student ID: {$student->id} ===");
            
        } catch (\Exception $e) {
            \Log::error("Error getting company name for student {$student->id}: " . $e->getMessage());
        }

        if ($option === 1) {
            return [
                "id" => $student->id,
                // Safely accessing nested properties with null checks
                "name" => $student->user ? $this->getFullName(
                    $student->user->first_name ?? "",
                    $student->user->middle_name ?? "",
                    $student->user->last_name ?? ""
                ) : "", // Return empty string if 'user' is null
                "email" => $student->user->email ?? "", // Fallback to empty string if 'email' is null
                "email_verified_at" => $this->formatDate($student->user->email_verified_at ?? null),
                "program" => $student->program ? $student->program->name : "", // Check if 'program' is null
                "college" => $student->program && $student->program->college ? $student->program->college->name : "", // Check if 'college' is null
                "date_of_birth" => $student->date_of_birth ? $this->formatDate($student->date_of_birth) : "No birth date",
                "status" => $student->status ? $student->status->name : "", // Check if 'status' is null
                "company" => $companyName,
                "company_name" => $companyName,
                "created_at" => $this->formatDate($student->created_at),
                "updated_at" => $this->formatDate($student->updated_at),
                "deleted_at" => $this->formatDate($student->deleted_at),
            ];
        } else if ($option === 2) {
            return [
                "id" => $student->id,
                // Safely accessing nested properties with null checks
                "name" => $student->user ? $this->getFullName(
                    $student->user->first_name ?? "",
                    $student->user->middle_name ?? "",
                    $student->user->last_name ?? ""
                ) : "", // Return empty string if 'user' is null
                "email" => $student->user->email ?? "", // Fallback to empty string if 'email' is null
                "coordinator" => $student->coordinator ? $this->getFullName(
                    $student->coordinator->first_name ?? "",
                    $student->coordinator->middle_name ?? "",
                    $student->coordinator->last_name ?? "",
                ) : "No Coordinator",
                "program" => $student->program ? $student->program->name : "", // Check if 'program' is null
                "company" => $companyName,
                "company_name" => $companyName,
                "date_of_birth" => $student->date_of_birth ? $this->formatDate($student->date_of_birth) : "No birth date",
                "status" => $student->status ? $student->status->name : "", // Check if 'status' is null
                "created_at" => $this->formatDate($student->created_at),
                "updated_at" => $this->formatDate($student->updated_at),
                "deleted_at" => $this->formatDate($student->deleted_at),
            ];
        }

        return [];
    }



    // Get authenticated student
    public function getAuthStudent()
    {

        // Get auth student
        $auth = Auth::user()->student;

        // Get latest application
        $auth['latest_application'] = $auth->latestApplication;

        return $this->jsonResponse($auth, 200);
    }

    // Get dashboard
    public function dashboard()
    {
        return User::all();
    }

    // Get Student record

    public function show(String $student_id)
    {
        return Student::where('user_id', $student_id)->get();
    }

    // Get all work posts
    public function workPosts()
    {
        return;
    }

    // Get all students
    public function index()
    {
        // Query users with a join
        // $companyUsers = User::whereHas('company')->get();
        /* $companyUsers = User::with('company')->select(['company_name'])->get(); */
        $studentUsers = User::join('students', 'users.id', '=', 'students.user_id')
            ->select(['users.*', 'students.age', 'students.isApplied'])
            ->get();

        return response()->json($studentUsers);
    }

    public function profile()
    {
        // Call the parent method to get the user data
        /* $userData = parent::profile();

        // Fetch additional student-specific data
        $studentInfo = Student::where('user_id', auth()->id())->first();

        // Combine both data into a single response
        return response()->json([
            'user' => $userData->getData(),
            'student' => $studentInfo
        ]); */
    }
}
