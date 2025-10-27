<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UploadLogoRequest;
use App\Http\Resources\ChairpersonProfileResource;
use App\Http\Resources\CompanyProfileResource;
use App\Http\Resources\CoordinatorProfileResource;
use App\Http\Resources\CoordinatorResource;
use App\Http\Resources\DeanProfileResource;
use App\Http\Resources\StudentProfileResource;
use App\Http\Resources\SupervisorProfileResource;
use App\Models\Company;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestProfileController extends Controller
{
    /**
     * A dean controller
     */
    private $deanController;
    /**
     * A chairperson controller
     */
    private $chairpersonController;
     /**
     * A coordinator controller
     */
    private $coordinatorController;
    /**
     * A company controller
     */
    private $companyController;
    /**
     * A student controller
     */
    private $studentController;
    /**
     * A cloud controller
     */
    private $cloudController;

    /**
     * TestProfileController
     */
    public function __construct(DeanController $deanController, ChairpersonController $chairpersonController, CompanyController $companyController, CloudController $cloudController, CoordinatorController $coordinatorController, StudentController $studentController)
    {
        $this->studentController = $studentController;
        $this->deanController = $deanController;
        $this->chairpersonController = $chairpersonController;
        $this->companyController = $companyController;
        $this->cloudController = $cloudController;
        $this->coordinatorController = $coordinatorController;
    }
    
    public function getSelfProfile(Request $request) {

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get Authenticated User
        $authUser = Auth::user();

        // Initalize Variable
        $profile = null;

        if($authUser->hasRole('dean') && $requestedBy === 'dean') {
            $profile = $this->deanController->findDean($authUser->id);

            // Transform to resource
            $profile = new DeanProfileResource($profile);
        }
        else if($authUser->hasRole('chairperson') && $requestedBy === 'chairperson') {
            $profile = $this->chairpersonController->findChairperson($authUser->id);

            // Transform to resource
            $profile = new ChairpersonProfileResource($profile);
        }
        else if($authUser->hasRole('coordinator') && $requestedBy === 'coordinator') {
            $profile = $this->coordinatorController->findCoordinator($authUser->id);

            // Transform to resource
            $profile = new CoordinatorProfileResource($profile);
        }
        else if($authUser->hasRole('company') && $requestedBy === 'company') {
            $profile = $this->companyController->findCompanyByID($authUser->id);

            // Transform to resource
            $profile = new CompanyProfileResource($profile);
        }
        else if($authUser->hasRole('supervisor') && $requestedBy === 'supervisor') {
            $profile = Supervisor::with(['user', 'company'])->find($authUser->id);

            // Transform to resource
            $profile = new SupervisorProfileResource($profile);
        }
        else if($authUser->hasRole('student') && $requestedBy === 'student') {
            $profile = Student::with(['user', 'program.college', 'coordinator', 'certificates'])->find($authUser->id);

            // Transform to resource
            $profile = new StudentProfileResource($profile);
        }

        return $this->jsonResponse($profile);
    }

    /**
     * Summary of updateUserProfileImage: A public function that updates the User Profile by ID.
     * @param \Illuminate\Http\Request $request
     * @param string $user_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateUserProfileImage(Request $request, String $user_id) {

        // Find User by ID
        $user = User::find($user_id);

        // Find User by ID

         // Ensure a profile file is provided and is an image with specific mime types
         $request->validate([
            'profile' => 'required|image|mimes:jpeg,png,jpg|max:20000',  // max size 10MB
        ]);

        if (!$request->hasFile('profile')) {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        if (!$request->file('profile')->isValid()) {
            return response()->json(['message' => 'Uploaded file is invalid.'], 400);
        }

        /**
         * The request is automatically validated at this point
         * Pass the request to the file cloud
         */
        $fileUrl = $this->cloudController->storeFileToCloud( request: $request, path: "public/uploads/users/profile/{$user->id}", fileName: 'profile');

        // Update and save
        $user->profile_image_url = $fileUrl;
        $user->save();

        // Return data(logo_url), message, and status
        return $this->jsonResponse([
            'message' => 'Profile photo is updated',
            'data' => $fileUrl,
        ], 201);

    } 

    /**
     * Summary of getCompanyLogoUrl: A public function that gets the logo of the company
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompanyLogoUrl() {

        // Get authenticated company user
        $authUser = Auth::user()->company;

        // Return Company Logo
        return $this->jsonResponse($authUser->logo_url, 200);

    }

    /**
     * Summary of updateUserCoverImage: A public function that updates a cover image by ID.
     * @param \Illuminate\Http\Request $request
     * @param string $user_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateUserCoverImage(Request $request, String $user_id) {

        // Find User by ID
        $user = User::find($user_id);

        // Find User by ID

         // Ensure a cover file is provided and is an image with specific mime types
         $request->validate([
            'cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20000',  // max size 10MB
        ]);

        if (!$request->hasFile('cover')) {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        if (!$request->file('cover')->isValid()) {
            return response()->json(['message' => 'Uploaded file is invalid.'], 400);
        }

        /**
         * The request is automatically validated at this point
         * Pass the request to the file cloud
         */
        $fileUrl = $this->cloudController->storeFileToCloud( request: $request, path: "public/uploads/users/profile/{$user->id}", fileName: 'cover');

        // Update and save
        $user->cover_image_url = $fileUrl;
        $user->save();

        // Return data(logo_url), message, and status
        return $this->jsonResponse([
            'message' => 'Cover photo is updated',
            'data' => $fileUrl,
        ], 201);
    }

    /**
     * Summary of updateCompanyLogo: A public function that updates a company logo.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateCompanyLogo(Request $request) {
        // Get authenticated user
        $authUser = Auth::user();

         // Ensure a logo file is provided and is an image with specific mime types
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20000',  // max size 10MB
        ]);

        if (!$request->hasFile('logo')) {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        if (!$request->file('logo')->isValid()) {
            return response()->json(['message' => 'Uploaded file is invalid.'], 400);
        }

        // Declare empty variable
        $company = null;

        // ! FOR ADMIN
        if($authUser->hasRole('admin')) {

            // Find Company by ID
            $company = $this->companyController->findCompanyByID($request['company_id']);

        }
        // ! FOR COMPANY
        else {
            // Find Company by ID
            $company = $this->companyController->findCompanyByID($authUser->id);
        }
        
        /**
         * The request is automatically validated at this point
         * Pass the request to the file cloud
         */
        // $fileUrl = $this->cloudController->storeFileToCloud( request: $request, path: "public/uploads/users/companies/profile/{$company->id}", fileName: 'logo');
        $fileUrl = $this->cloudController->storeFileToCloud( request: $request, path: "public/uploads/users/companies/logos/{$company->id}", fileName: 'logo');

        // Update and save company
        $company->logo_url = $fileUrl;
        $company->save();

        // Return data(logo_url), message, and status
        return $this->jsonResponse([
            'message' => 'Logo is updated',
            'data' => $fileUrl,
        ], 201);
    }

    /**
     * Summary of getStudentProfile: A public function that gets the profile of the student
     * @param string $student_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentProfile(String $student_id) {
       
        // Find Student
        /* $student = $this->studentController->findStudent($student_id)->with(['workExperiences', 'educations', 'user', 'program.college', 'coordinator.user'])->find($student_id); */

        $student = Student::with(['workExperiences', 'educations', 'user', 'program.college', 'coordinator.user'])->find($student_id);

        // Return resource
        return $this->jsonResponse(new StudentProfileResource($student));
    }

    /**
     * Summary of getCompanyProfile: A public function that gets the company profile by ID.
     * @param string $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompanyProfile(String $company_id) {
         // Find Company
         $company = $this->companyController->findCompanyByID($company_id);

         // Check if company does not exist.
         if(!$company) {
             abort(404, 'Company not found.');
         }
         
          // Return resource
        return $this->jsonResponse(new CompanyProfileResource($company));
    }

    /**
     * Summary of getCoordinatorProfile: A public function that gets the coordinators details by ID.
     * @param string $coordinator_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCoordinatorProfile(String $coordinator_id) {

        // Find Coordinator by ID
        $coordinator = $this->coordinatorController->findCoordinator($coordinator_id);
        
        // Return Resource
        return $this->jsonResponse(new CoordinatorProfileResource($coordinator));
    }

    /**
     * Summary of getMyCompanyProfile: A public function that gets the profile of the company (authenticated)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyCompanyProfile()
    {

        // Get authenticated user
        $authUser = Auth::user();


        // Find Company
        $company = $this->companyController->findCompanyByID($authUser->company->id);

        // Check if company does not exist.
        if(!$company) {
            abort(404, 'Company not found.');
        }


        // Return resource
        return $this->jsonResponse(new CompanyProfileResource($company));
    }

  
    public function updateProfile( ProfileRequest $profileRequest, String $user_id) {

        // Get validated 
        $validated = $profileRequest->validated();

        // Find user
        $user = User::find($user_id);

        // Update and save
        $user->update($validated);
        $user->save();

        // ! FOR COMPANY
        if($user->hasRole('company')) {

            // Find company by ID
            $company = $this->companyController->findCompanyByID($user->id);

            // Update and save
            $company->update($validated);
            $company->save();

        }
        // ! FOR STUDENT
        else if($user->hasRole('student')) {

            // Find student by ID
            $student = Student::find($user->id);

            // Update and save
            $student->about_me = $validated['about_me'];
            $student->date_of_birth = $validated['date_of_birth'];
            $student->save();

        }
        
        // Return status, and message
        return $this->jsonResponse([
            'message' => 'Profile is updated'
        ], 201);
    }

    /**
     * Summary of getDeanProfile: A public function that gets the profile of dean.
     * @param string $dean_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeanProfile(String $dean_id)
    {

        // Find Dean by ID
        $dean = $this->deanController->findDean($dean_id);

        // Check if dean does not exist.
        if (!$dean) {
            abort(404, 'Dean not found.');
        }

        // Return resource
        return $this->jsonResponse(new DeanProfileResource($dean));
    }

    /**
     * Summary of getChairpersonProfile: A public function that gets the profile of chairperson.
     * @param string $chairperson_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChairpersonProfile(String $chairperson_id)
    {

        // Find Chairperson by ID
        $chairperson = $this->chairpersonController->findChairperson($chairperson_id);

        // Return resource
        return $this->jsonResponse(new ChairpersonProfileResource($chairperson));
    }
}
