<?php

namespace App\Services;

use App\Repositories\ApplicationRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApplicationService
{

    // Repository
    private $applicationRepositoryInterface;

    // Model
    private $authUser;

    /**
     * Create a new class instance.
     */
    public function __construct(ApplicationRepositoryInterface $applicationRepositoryInterface)
    {
        $this->authUser = Auth::user();
        $this->applicationRepositoryInterface = $applicationRepositoryInterface;
    }

    public function exists() {}

    /**
     * Summary of getByID: Get application by ID.
     * @param string $applicationID
     * @param array $filters
     * @return mixed
     */
    public function getByID(string $applicationID, array $filters)
    {

        // Get application
        $application = $this->applicationRepositoryInterface->getByID($filters, $applicationID);

        // Check if application does exist
        if (!$application) {
            abort(Response::HTTP_NOT_FOUND, 'Application not found.');
        }

        // Return application
        return $application;
    }

}
