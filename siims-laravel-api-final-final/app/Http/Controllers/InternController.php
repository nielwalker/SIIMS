<?php

namespace App\Http\Controllers;

use App\Http\Requests\DtrMarkStatusRequest;
use App\Models\DtrEntry;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InternController extends Controller
{
    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct()
    {
        $this->user = Auth::user(); // Initialize the authenticated user
    }
    
    
    public function markStatus(DtrMarkStatusRequest $request, String $dtrId) {

        // Get validated
        $validated =  $request->validated();

        // Fin DTR Entry by ID
        $dtrEntry = DtrEntry::find($dtrId);

        if(!$dtrEntry) {
            return response()->json(['message' => 'DTR not found.'], 404);
        }

        // Update Dtr
        $dtrEntry->status_id = $validated['status_id'];
        $dtrEntry->save();

        // Return response
        return response()->json(['message' => 'DTR is updated.'], 201);
    }

    public function getAllInterns()
    {
        // Get Supervisor
        $supervisor = Supervisor::where('user_id', $this->user->id)->first();

        // Check if Supervisor exists
        if (!$supervisor) {
            return response()->json(['message' => 'Supervisor not found.'], 404);
        }

        // Check if Supervisor has an office
        $office = $supervisor->company->offices()->first();
        if (!$office) {
            return response()->json(['message' => 'Supervisor does not manage an office.'], 404);
        }

        // Fetch all students (interns) with status_id = 12 through work_posts and applications
        $interns = $office->workPosts()
            ->with(['applications.student.user', 'applications.student.program', 'applications.student.status'])
            ->get()
            ->flatMap(function ($workPost) {
                return $workPost->applications->filter(function ($application) {
                    // Ensure the student exists and has status_id = 12 or 4
                    return $application->student && ($application->student->status_id === 12 || $application->student->status_id === 4);
                })->map(function ($application) {
                    return [
                        'application' => $application,       // Include the entire application
                        'student' => $application->student, // Include student data
                    ];
                });
            })
            ->unique('student.id'); // Ensure unique students by student ID

        // Transform intern data to include application and student details
        $transformedInterns = $interns->map(function ($item) {
            $application = $item['application'];
            $student = $item['student'];
            return [
                'application_id' => $application->id,
                'id' => $student->id,
                'name' => $student->user ? $this->getFullName(
                    $student->user->first_name ?? "",
                    $student->user->middle_name ?? "",
                    $student->user->last_name ?? ""
                ) : "No name",
                'course' => $student->program->name,
                'status' => $student->status->name,
            ];
        });

        // Return the list of interns with merged application and student details
        return response()->json($transformedInterns, 200);
    }

    public function getAllInternsDailyTimeRecords(String $application_id)
    {
        // Fetch the Daily Time Records for the given application_id, sorted by creation date (oldest first)
        $dtrEntries = DtrEntry::with(['status'])->where('application_id', $application_id)
        ->orderBy('created_at', 'asc') // Ascending order, so the first created is at the top
        ->get();
    
        // Check if no records are found
        if ($dtrEntries->isEmpty()) {
            return response()->json(['message' => 'No Daily Time Records Yet'], 404);
        }   

        // Transform Daily Time Records
        $transformedDtr = $dtrEntries->map(function ($dtr) {
            return [
                "id" => $dtr->id,
                "date" => $dtr->date,
                "timeIn" => $dtr->time_in,
                "timeOut" => $dtr->time_out,
                "hours" => $dtr->hours_received,
                "status" => $dtr->status ? $dtr->status->name : "No Status Yet",
            ];
        });
        
        // Return the sorted Daily Time Records
        return response()->json($transformedDtr, 200);
    }
    
}
