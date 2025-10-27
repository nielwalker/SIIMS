<?php

namespace App\Repositories;

interface ApplicationRepositoryInterface
{
    public function create(string $workPostID);

    public function getByID(array $filters = [], string $applicationID);

    public function isExistingApplication(string $workPostID);


    public function exists(int $studentID, int $applicationID, array $filters);

    public function findByStudentID(string $applicationID, string $studentID);
}
