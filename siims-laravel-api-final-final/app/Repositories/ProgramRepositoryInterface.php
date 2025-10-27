<?php

namespace App\Repositories;

interface ProgramRepositoryInterface
{
    
    public function getTotalPrograms();

    public function findChairperson(String $userID);

    public function getProgramByChairperson(String $userID);

}
