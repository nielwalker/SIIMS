<?php

namespace App\Services;

use App\Repositories\ProgramRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class ProgramService
{

    // Repository
    private $programRepositoryInterface;


    /**
     * Create a new class instance.
     */
    public function __construct(ProgramRepositoryInterface $programRepositoryInterface)
    {
        $this->programRepositoryInterface = $programRepositoryInterface;
    }


    // Get program if chairperson exist
    public function getProgramIfChairpersonExist(String $userID)
    {

        $program = $this->programRepositoryInterface->findChairperson($userID);

        // Check if not exist
        if (!$program) {
            abort(Response::HTTP_UNAUTHORIZED, 'Program not found or not assigned to this chairperson.');
        }

        // Return
        return $program;
    }
}
