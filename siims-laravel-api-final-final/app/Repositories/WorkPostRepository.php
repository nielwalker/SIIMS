<?php

namespace App\Repositories;

use App\Models\WorkPost;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WorkPostRepository implements WorkPostRepositoryInterface
{

    // Model variable
    private $workPost;

    /**
     * Create a new class instance.
     */
    public function __construct(WorkPost $workPost)
    {
        
        $this->workPost = $workPost;
    }

    public function getTotalWorkPosts() {
        return $this->workPost->count();
    }

    /**
     * Summary of find: A public function that finds or fail work post by ID.
     * @param string $id
     * @return WorkPost
     */
    public function find(string $id): WorkPost
    {

        // Find or fail
        $workPost = $this->workPost->findOrFail($id);

        // Return 
        return $workPost;
    }

    /**
     * Summary of isExpired: A public function that checks if the job post is expired.
     * @param string $endDate
     * @return bool
     */
    public function isExpired(string $endDate) {
        return now()->greaterThan($endDate);
    }

    /**
     * Summary of isMaxedApplicants: A public function that checks if max applicnts
     * @param \App\Models\WorkPost $workPost
     * @return bool
     */
    public function isMaxedApplicants(WorkPost $workPost) {
        return $workPost->applications->count() >= $workPost->max_applicants;
    }
}
