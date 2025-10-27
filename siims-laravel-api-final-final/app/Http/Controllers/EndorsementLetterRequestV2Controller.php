<?php

namespace App\Http\Controllers;

use App\Http\Resources\EndorsementLetterRequestV2Resource;
use App\Models\EndorsementLetterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EndorsementLetterRequestV2Controller extends Controller
{

    /**
     * Summary of getEndorsementRequestByID: A public function that gets the endorsement requests by ID.
     * @param \Illuminate\Http\Request $request
     * @param string $endorsment_request_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEndorsementRequestByID(Request $request, String $endorsment_request_id)
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Define the requested role by role
        $requestedBy = (string) $request->input('requestedBy');

        // Find endorsement letter (Chairperson Part)
        $endorsementLetterRequest = EndorsementLetterRequest::with([
            'student.user',
            'endorseStudents.student.user',
            'student.program.college',
        ])->whereHas('student.program', function ($query) use ($authUser) {
            $query->where('chairperson_id', $authUser->id);
        })->find($endorsment_request_id);

        // Return
        return $this->jsonResponse(new EndorsementLetterRequestV2Resource($endorsementLetterRequest));
    }
}
