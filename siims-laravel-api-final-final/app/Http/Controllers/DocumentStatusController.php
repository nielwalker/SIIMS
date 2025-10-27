<?php

namespace App\Http\Controllers;

use App\Models\DocumentStatus;
use Illuminate\Http\Request;

class DocumentStatusController extends Controller
{
    
    /**
     * Summary of getAllDocumentSubmissionStatuses: A public function that gets all document submissions
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllDocumentSubmissionStatuses(Request $request) {

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Get all statuses
        $status = DocumentStatus::whereIn('id', [1,4,5])->get();

        // Return status
        return $this->jsonResponse($status,200);

    }
}
