<?php

namespace App\Http\Controllers;

use App\Models\ApplicationStatus;
use Illuminate\Http\Request;

class ApplicationStatusController extends Controller
{
    /**
     * Summary of getAllApplicationStatuses: A public function that gets all application statuses.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllApplicationStatuses() {
        $applicationStatuses = ApplicationStatus::all();

        return $this->jsonResponse($applicationStatuses, 200);
    }
}
