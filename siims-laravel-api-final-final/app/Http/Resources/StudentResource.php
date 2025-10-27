<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class StudentResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get authenticated user
        /**
         * @var User
         */
        $authUser = Auth::user();

        // Use the UserResource to structure the base user data
        $userData = (new UserResource($this->user))->toArray($request);

        // * Remove the 'roles, deleted_at' attribute
        unset($userData['roles']);

        // ! FOR ADMIN
        if ($authUser->hasRole('admin')) {

            // Merge array of resources
            $userData = array_merge($userData, [
                "program_id" => $this->program->id,
                "program_name" => $this->program->name,
                "college" => $this->program->college->name,
                "student_status" => $this->studentStatus->name,
                "total_applications" => $this->applications_count,
                "total_endorsement_requests" => $this->endorsement_letter_requests_count,
                "coordinator" => $this->coordinator ? $this->getFullName(
                    $this->coordinator->user->first_name ?? "",
                    $this->coordinator->user->middle_name ?? "",
                    $this->coordinator->user->last_name ?? "",
                ) : "No Coordinator",
                "company" => $this->company ? $this->company->name : "—",
                "company_name" => $this->company ? $this->company->name : "—"
            ]);
        }

        // ! FOR DEAN
        else if ($authUser->hasRole('dean')) {
            // Merge array of resources
            $userData = array_merge($userData, [
                "program_id" => $this->program->id,
                "program_name" => $this->program->name,
                "coordinator" => $this->coordinator ? $this->getFullName(
                    $this->coordinator->user->first_name ?? "",
                    $this->coordinator->user->middle_name ?? "",
                    $this->coordinator->user->last_name ?? "",
                ) : "No Coordinator",
                "company" => $this->company ? $this->company->name : "—",
                "company_name" => $this->company ? $this->company->name : "—"
            ]);
        }

        // ! FOR COORDINATOR
        else if ($authUser->hasRole('coordinator') && $request['requestedBy'] === 'coordinator') {
          
             // Merge array of resources
             $userData = array_merge($userData, [
                "program_id" => $this->program->id,
                "program_name" => $this->program->name,
                "status_id" => $this->student_status_id,
                "status" => $this->studentStatus->name,
                "student_status" => $this->studentStatus->name,
                "latest_application_id" => $this->latestApplication ? $this->latestApplication->id : null,
                "program" => $this->program,
                "college" => $this->program->college,
               'latest_application' => $this->latestApplication ?   $this->latestApplication : null,
               'applications' => $this->applications,
                'total_applications' => $this->applications_count,
                'certificates' => $this->certificates,
            ]);
        }

        // ! FOR CHAIRPERSON
        else if ($authUser->hasRole('chairperson')) {
            // Merge array of resources
            $userData = array_merge($userData, [
                "coordinator" => $this->coordinator ? $this->getFullName(
                    $this->coordinator->user->first_name ?? "",
                    $this->coordinator->user->middle_name ?? "",
                    $this->coordinator->user->last_name ?? "",
                ) : "No Coordinator",
                "company" => $this->company ? $this->company->name : "—",
                "company_name" => $this->company ? $this->company->name : "—"
            ]);
        }

        // Access status from meta and modify userData
        if (isset($this->additionalMeta) && $this->additionalMeta['status'] === 'Pending') {
            $userData = array_merge($userData, [
                'latest_application_id' => $this->latestApplication->id,
            ]);
        }

        // Merge array
        $userData = array_merge($userData, [
            'daily_entries' => $this->dailyEntries,
            'has_requested_endorsement' => $this->has_requested_endorsement ? "Requested" : "Not Yet",
        ]);

        // Return resources
        return $userData;
    }
}
