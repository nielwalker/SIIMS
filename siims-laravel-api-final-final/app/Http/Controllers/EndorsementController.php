<?php

namespace App\Http\Controllers;

use App\Http\Resources\EndorsementResource;
use App\Models\EndorsementLetterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EndorsementController extends Controller
{   

    /**
     * Summary of queryEndorsement: A private function that queries the endorsement letter request.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function queryEndorsement() {

        // Get authenticated user
        $student = Auth::user()->student;

        $query = EndorsementLetterRequest::with([
            'endorsementLetterRequestStatus', 
            'endorseStudents.student.user',
            'workPost.office.company'
        ])
        ->where('requested_by_id', $student->id) // Where student is the requester
        ->orWhereHas('endorseStudents', function ($query) use ($student) {
            $query->where('student_id', $student->id); // Where student is included in the endorsement
        });

        // Return
        return $query;

    }

    /**
     * Summary of findEndorsementByWorkPostID: A public function that finds the endorsement by work post ID.
     * @param string $work_post_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function findEndorsementByWorkPostID(String $work_post_id) {

        // Get authenticated user
        $student = Auth::user()->student;

        $query = EndorsementLetterRequest::with([
            'endorsementLetterRequestStatus', 
            'endorseStudents.student.user',
            'workPost.office.company'
        ])->where('work_post_id', $work_post_id)->where('requested_by_id', $student->id)->orWhereHas('endorseStudents', function ($query) use ($student) {
            $query->where('student_id', $student->id); // Where student is included in the endorsement
        })->first();

        // Check if the query result is null
    if (!$query) {
        return response()->json(null, 200); // Return a 404 response
    }

        // Return 
        return $this->jsonResponse(new EndorsementResource($query));

    }

    /**
     * Summary of getAllEndorsements: Get all endorsement letter requests related to the authenticated student.
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAllEndorsements(Request $request)
    {
        // Get authenticated user
        $student = Auth::user()->student;

        if (!$student) {
            return response()->json(['message' => 'Student data not found or unauthorized.'], 403);
        }

        // Define the number of items per page (default to 5)
        $perPage = (int) $request->input('perPage', 5);

        // Get the endorsements where the student is either the requester or included in the request
        $query = $this->queryEndorsement();

        // Paginate the results
        $endorsements = $query->paginate($perPage);

        // Return the result as a resource collection
        return EndorsementResource::collection($endorsements);
    }
}
