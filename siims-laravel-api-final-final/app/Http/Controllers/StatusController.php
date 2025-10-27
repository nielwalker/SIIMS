<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{

   

    public function getAllDocumentStatuses () {
        // Get Statuses
        $statuses = Status::whereIn('id', ['1', '2', '3', '5', '7'])->get();

        return response()->json($statuses, 200);
    }

    //
    public function getAllStatuses() {

        // Get all statuses
        $statuses = Status::all();

        // Return response
        return response()->json($statuses, 200);

    }
}
