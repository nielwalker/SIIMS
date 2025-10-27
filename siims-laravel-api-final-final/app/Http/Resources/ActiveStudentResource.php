<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ActiveStudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // Get authenticated user
        $user = Auth::user();

        // Use the UserResource to structure the base user data
        $userData = (new UserResource($this->user))->toArray($request);

        // * Remove 'roles' attribute
        unset($userData['roles']);

        // ! For Company, Supervisor and Coordinator
        if ($user->hasRole('company') || $user->hasRole('supervisor') || $user->hasRole('coordinator')) {

            $userData = array_merge($userData, [
                "application_id" => $this->latestApplication ? $this->latestApplication->id : "",
                "job_title" => $this->latestApplication->workPost->title,
                "student_status_id" => $this->student_status_id,
                "student_status" => $this->studentStatus->name,
            ]);


            // ! For Company and Supervisor
            if ($user->hasRole('company') || $user->hasRole('supervisor')) {

                $userData = array_merge($userData, [
                    "office_name" => $this->latestApplication->workPost->office->name,
                    "company_name" => $this->latestApplication->workPost->office->company->name,
                    "no_of_hours" => $this->latestApplication->workPost->work_duration,
                    "company_street" => $this->latestApplication->workPost->office->company->user->street,
                    "company_barangay" => $this->latestApplication->workPost->office->company->user->barangay,
                    "company_city_municipality" => $this->latestApplication->workPost->office->company->user->city_municipality,
                    "company_provice" => $this->latestApplication->workPost->office->company->user->province,
                    "company_postal_code" => $this->latestApplication->workPost->office->company->user->postal_code,
                ]);
            }
        }

        // Return resources
        return $userData;
    }
}
