<?php

namespace App\Repositories;

use App\Models\WorkPost;

interface WorkPostRepositoryInterface
{
    public function find(string $id);
    public function isExpired(string $endDate);
    public function isMaxedApplicants(WorkPost $workPost);

    public function getTotalWorkPosts();

}
