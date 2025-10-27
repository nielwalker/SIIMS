<?php

namespace App\Repositories;

interface EndorseStudentRepositoryInterface
{
    public function create(array $studentIDs, string $endorsementLetterRequestID);

}
