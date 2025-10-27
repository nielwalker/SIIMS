<?php

namespace App\Http\Controllers;

use App\Models\WeeklyEntry;
use Illuminate\Http\Request;

class InternWeeklyReportController extends Controller
{
    //

    public function getAllWeeklyReports(String $application_id) {

        // Find Weekly Accomplishment Reports
        $wars = WeeklyEntry::where('application_id', $application_id)->get();

        if(!$wars) {
            return response()->json(['message' => 'Weekly Accomplishments not found.'], 404);
        }   
        // Transform 
        $transformedWars =  $wars->map(function ($war) {
            return [
                "id" => $war->id,
                "start_date" => $this->formatDateOnlyDate($war->start_date),
                "end_date" => $this->formatDateOnlyDate($war->end_date),
                "tasks" => $war->tasks,
                "learnings" => $war->learnings,
                "no_of_hours" => $war->no_of_hours,
                "date_submitted" => $this->formatDateOnlyDate($war->created_at),
            ];
        });

        // Return response with status 200
        return response()->json( $transformedWars, 200);

        // Transform Response

    }
}
