<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobPostingResource;
use App\Http\Resources\WorkPostResource;
use App\Models\WorkPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobPostingController extends Controller
{
    
    /**
     * Summary of fetchWorkPostsForStudent: A public function that gets all jobs
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function fetchWorkPostsForStudent(Request $request) {
        $authUser = Auth::user()->student;
    
        if (!($authUser->student_status_id == 1 && $authUser->coordinator_id)) {
            return $this->jsonResponse([], 200);
        }
    
        $perPage = (int) $request->input('perPage', 5);
        $searchTerm = $this->sanitizeAndGet($request);
    
        $query = WorkPost::has('office')
            ->with(['workType', 'office', 'office.company.user']);
    
        if (!empty($searchTerm)) {
            $query->where('title', 'LIKE', '%' . strtolower($searchTerm) . '%')->orWhereHas('office.company', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
            });
        }
    
        if ($request->has('work_type_id')) {
            $query->where('work_type_id', $request->input('work_type_id'));
        }
    
        // Randomize the query results
        $query->inRandomOrder();

        $workPosts = $query->paginate($perPage);
    
        return JobPostingResource::collection($workPosts);
    }
    
}
