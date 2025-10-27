<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Company;
use App\Models\WorkPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardControllerV2 extends Controller
{

    /**
     * Summary of company: A private function that fetches the details necessary for comapany dashboard
     * @param string $id
     * @return array
     */
    private function company(String $id)
    {

        // Initialize associative array
        $details = [];

        // Get all current interns (application_status ID: 6)
        $details['total_current_interns'] = Application::whereHas('workPost.office.company', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->where('application_status_id', 6)->count();


        /**
         * - Check if the company ID is from USTP
         * - Count applications that have at least one documentSubmission with document_type_id = 2
         */
        $details['total_pending_applications'] = Application::whereHas('workPost.office.company', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->where('application_status_id', 1);

        /**
         * -  Do not count those application that has no doc_type_id exist
         * -   Else allow count
         */
        if ($id === $this->COMPANY_ID) {
            // Count applications that have at least one documentSubmission with document_type_id = 2
            $details['total_pending_applcations'] = $details['total_pending_applcations']->whereHas('documentSubmissions', function ($query) {
                    $query->where('doc_type_id', 2);
                })->count();
        } else {
            // Count applications that have at least one documentSubmission with document_type_id = 2
            $details['total_pending_applications'] = $details['total_pending_applications']->count();
                
        }

        // Get all work posts
        $details['total_work_posts'] = WorkPost::whereHas('office.company', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->count();

        // Return associative array details
        return $details;
    }

    /**
     * Summary of getDashboard: A public function that gets all details for user dashboards
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboard(Request $request)
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Defind the requested role by 
        $requestedBy = (string) $request->input('requestedBy');

        // Initialize variable
        $dashboardDetails = null;


        // dd($authUser->id);

        // If requested by company
        if ($authUser->hasRole('company') && $requestedBy === 'company') {

            // Find Company
            $company = Company::where('user_id', $authUser->id)->first();
            

            // Merge Array
            $dashboardDetails = array_merge($this->company($authUser->id), [
                "name" => $company->name ?? "",
            ]);

        } else {
            // Return response
            return $this->jsonResponse(['message' => 'Not found.'], 404);
        }


        // Return
        return $this->jsonResponse($dashboardDetails, 200);
    }
}
