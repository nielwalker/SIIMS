<?php

namespace App\Repositories;

interface EndorsementLetterRequestRepositoryInterface
{
    
    public function exists(string $requestedByID, string $workPostID);
    public function create(array $validated, array $filters);

    public function get(array $filters);
    public function getArchives(array $filters); 

    public function existsWithinLastFiveDays(string $requestedByID, array $studentIds);

    public function find(string $id);
    public function findArchive(string $id);

}
