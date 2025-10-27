<?php

namespace App\Repositories;

interface LogRepositoryInterface
{
    
    public function create(String $entityID = "N/A", String $model, String $http_code, String $actionType);

}
