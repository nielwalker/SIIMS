<?php

namespace App\Http\Controllers;

use App\Models\TimeRecordStatus;
use Illuminate\Http\Request;

class DtrStatusController extends Controller
{
    //

    /**
     * Summary of getAllDailyTimeRecordStatuses: A public function that gets all Time Record Statuses.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllDailyTimeRecordStatuses() {

        $timeRecordStatuses = TimeRecordStatus::all();

        return $this->jsonResponse($timeRecordStatuses, 200);

    }
}
