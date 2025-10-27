<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // Get authenticated user
        $authUser = Auth::user();

        // General resource
        $resources = [
            "id" => $this->id,
            "student_id" => $this->student->id,
            "name" => $this->getFullName(
                $this->student->user->first_name ?? "",
                $this->student->user->middle_name ?? "",
                $this->student->user->last_name ?? "",
            ),
            "job_title" => $this->workPost->title,
            "email" => $this->student->user->email,
            "phone_number" => $this->student->user->email,
            "status" => $this->applicationStatus->name,
        ];

        /**
         * Check if the student status is completed (6)
         */
        if ($this->application_status_id === 6) {
           // Merged new array
            $resources = array_merge($resources, [
                'reports' => $this->reports->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'name' => $report->reportType->name,
                        'file_path' => $report->file_path,
                    ];
                })->toArray(),
            ]);

        }

        // ! FOR COMPANY
        if ($authUser->hasRole('company')) {

            // Merge array
            $resources = array_merge($resources, [
                "office" => $this->workPost->office->name,
                "supervisor" => $this->workPost->office->supervisor ? $this->getFullName(
                    $this->workPost->office->supervisor->user->first_name ?? "",
                    $this->workPost->office->supervisor->user->middle_name ?? "",
                    $this->workPost->office->supervisor->user->last_name ?? "",
                ) : "",
            ]);
        }

        // ! FOR COORDINATOR
        else if ($authUser->hasRole('coordinator')) {

            // Merge array
            $resources = array_merge($resources, [
                "company" => $this->workPost->office->company->name,
                "office" => $this->workPost->office->name,
                "supervisor" => $this->workPost->office->supervisor ? $this->getFullName(
                    $this->workPost->office->supervisor->user->first_name ?? "",
                    $this->workPost->office->supervisor->user->middle_name ?? "",
                    $this->workPost->office->supervisor->user->last_name ?? "",
                ) : "",
            ]);
        }

        // Return resources
        return $resources;
    }
}
