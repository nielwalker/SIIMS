<?php

namespace App\Repositories;

interface ProfileRepositoryInterface
{
    public function get(array $filters);
    public function queryGet(array $filters);
}
