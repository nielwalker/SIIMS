<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardRepository implements DashboardRepositoryInterface
{

    /**
     * @var User|null
     */
    private $authUser;


    /**
     * Repository variable
     */
    private $collegeRepositoryInterface;
    private $officeRepositoryInterface;
    private $workPostRepositoryInterface;
    private $programRepositoryInterface;

    private $userRepositoryInterface;
    private $studentRepositoryInterface;
    private $companyRepositoryInterface;
    private $deanRepositoryInterface;
    private $supervisorRepositoryInterface;
    private $coordinatorRepositoryInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(CollegeRepositoryInterface $collegeRepositoryInterface, OfficeRepositoryInterface $officeRepositoryInterface, WorkPostRepositoryInterface $workPostRepositoryInterface, ProgramRepositoryInterface $programRepositoryInterface, UserRepositoryInterface $userRepositoryInterface, StudentRepositoryInterface $studentRepositoryInterface, CompanyRepositoryInterface $companyRepositoryInterface, DeanRepositoryInterface $deanRepositoryInterface, SupervisorRepositoryInterface $supervisorRepositoryInterface, CoordinatorRepositoryInterface $coordinatorRepositoryInterface)
    {

        $this->authUser = Auth::user();
        $this->collegeRepositoryInterface = $collegeRepositoryInterface;
        $this->officeRepositoryInterface = $officeRepositoryInterface;
        $this->workPostRepositoryInterface = $workPostRepositoryInterface;
        $this->programRepositoryInterface = $programRepositoryInterface;
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->studentRepositoryInterface = $studentRepositoryInterface;
        $this->companyRepositoryInterface = $companyRepositoryInterface;
        $this->deanRepositoryInterface = $deanRepositoryInterface;
        $this->supervisorRepositoryInterface = $supervisorRepositoryInterface;
        $this->coordinatorRepositoryInterface = $coordinatorRepositoryInterface;
    }

    /**
     * Summary of get: A public function that gets the data for dashboard
     * @param array $filters
     * @return array
     */
    public function get(array $filters)
    {
        // Contain Totals
        $totalColleges = $this->collegeRepositoryInterface->getTotalColleges();
        $totalOffices = $this->officeRepositoryInterface->getTotalOffices();
        $totalPrograms = $this->programRepositoryInterface->getTotalPrograms();
        $totalWorkPosts = $this->workPostRepositoryInterface->getTotalWorkPosts();

        // Role-aware total students:
        // - Coordinator: count only students assigned to this coordinator
        // - Chairperson/Admin/Dean: keep existing global/program/college behavior as implemented by getTotalStudents()
        $requestedBy = $filters['requestedBy'] ?? '';
        $totalStudents = $this->studentRepositoryInterface->getTotalStudents();
        if ($this->authUser && $this->authUser->hasRole('coordinator') && $requestedBy === 'coordinator') {
            // Use repository get() which already scopes by coordinator when requestedBy='coordinator'
            $totalStudents = $this->studentRepositoryInterface->get([
                'requestedBy' => 'coordinator',
                'searchTerm' => ''
            ])->count();
        }

        // Intialize empty associative array
        $data = [
            'total_colleges' => $totalColleges,
            'total_programs' => $totalPrograms,
            'total_offices' => $totalOffices,
            'total_work_posts' =>  $totalWorkPosts,
            'total_users' => $this->userRepositoryInterface->getTotalUsers(),
            'total_students' => $totalStudents,
            'total_companies' => $this->companyRepositoryInterface->getTotalCompanies(),
            'total_deans' => $this->deanRepositoryInterface->getTotalDeans(),
            'total_supervisors' => $this->supervisorRepositoryInterface->getTotalSupervisors(),
            'total_coordinators' => $this->coordinatorRepositoryInterface->getTotalCoordinators(),
            'bar_chart' => [
                [
                    "name" => 'Colleges',
                    "value" => $totalColleges,
                ],
                [
                    'name' => 'Offices',
                    "value" => $totalColleges,
                ],
                [
                    'name' => 'Jobs',
                    "value" => $totalWorkPosts,
                ],
                [
                    'name' => 'Programs',
                    "value" => $totalPrograms,
                ],
            ]
        ];

        // Return
        return $data;
    }
}
