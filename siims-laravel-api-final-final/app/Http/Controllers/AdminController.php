<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminCampusRequest;
use App\Http\Requests\AdminCompanyRequest;
use App\Http\Requests\AdminProfileRequest;
use App\Http\Requests\AdminStudentRequest;
use App\Http\Requests\AdminUserRequest;
use App\Models\Campus;
use App\Models\College;
use App\Models\Company;
use App\Models\Coordinator;
use App\Models\Office;
use App\Models\Program;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\WorkPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{

    // Update the user profile
    // TODO:
    public function updateProfile(AdminProfileRequest $request) {}

    // Get profile of the admin user
    public function profile()
    {
        // Get auth user
        $user = Auth::user()->roles;

        // Return
        return response()->json($user, 200);
    }

    // Get data for sidebar
    public function sidebar()
    {
        // Call the parent Controller's sidebar method
        return parent::sidebar();
    }

    public function users()
    {
        return User::all();
    }

    // Get the data for dashboard
    public function dashboard()
    {

        // Get total colleges
        $total_colleges = College::count();

        // Get total programs
        $total_programs = Program::count();

        // Get total offices
        $total_offices = Office::count();

        // Get total work posts
        $total_work_posts = WorkPost::count();

        // Get total users
        $total_users = User::count();
        // Get total students
        $total_students = Student::count();
        // Get total supervisors
        $total_supervisors = Supervisor::count();
        // Get total coordinators
        $total_coordinators = Coordinator::count();
        // Get total companies
        $total_companies = Company::count();
        // Get total chairpersons
        $total_chairpersons = User::whereHas('roles', function ($query) {
            $query->where('role_id', 2);
        })->count();
        // Get total deans
        $total_deans = User::whereHas('roles', function ($query) {
            $query->where('role_id', 5);
        })->count();

        return response()->json([
            "total_colleges" => $total_colleges,
            "total_programs" => $total_programs,
            "total_offices" => $total_offices,
            "total_work_posts" => $total_work_posts,
            "total_users" => $total_users,
            "total_students" => $total_students,  
            "total_companies" => $total_companies,  
            "total_deans" => $total_deans,
            "total_chairpersons" => $total_chairpersons,
            "total_supervisors" => $total_supervisors,
            "total_coordinators" => $total_coordinators,
        ]);
    }
}
