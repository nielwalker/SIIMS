<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Company;
use App\Models\Coordinator;
use App\Models\Office;
use App\Models\Program;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\WorkPost;
use App\Repositories\DashboardRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    /**
     * The authenticated user.
     *
     * @var User/null
     */
    private $user;


    // Repository
    private $dashboardRepositoryInterface;

    /**
     * DashboardController constructor.
     */
    public function __construct(DashboardRepositoryInterface $dashboardRepositoryInterface)
    {
        $this->user = Auth::user(); // Initialize the authenticated user
        $this->dashboardRepositoryInterface = $dashboardRepositoryInterface;
    }

    /**
     * Summary of get: A public function that gets all data for Dashboard.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {

        // Add filters
        $filters = [
            'requestedBy' => $request->query('requestedBy'),
        ];

        // Fetch data
        $data = $this->dashboardRepositoryInterface->get($filters);

        // Return
        return $this->jsonResponse($data, 200);
    }
}
