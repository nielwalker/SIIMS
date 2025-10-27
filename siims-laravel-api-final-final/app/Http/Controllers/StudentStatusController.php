<?php

namespace App\Http\Controllers;

use App\Models\StudentStatus;
use Illuminate\Http\Request;

class StudentStatusController extends Controller
{
    
    /**
     * Summary of getAllStudentStatuses: A public function that gets all Student Statuses.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllStudentStatuses() {

        $studentStatuses = StudentStatus::all();

        return $this->jsonResponse($studentStatuses, 200);
    }
}
