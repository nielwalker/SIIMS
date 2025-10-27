<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentHomeResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Use the UserResource to structure the base user data
        $userData = (new UserResource($this->user))->toArray($request);

         // Remove the roles attribute
         unset($userData['roles']);

        // Remove the user attribute
        // unset($this->user);

        // Return merged resource
        return array_merge($userData, [
            'work_experiences' => $this->workExperiences,
            'educations' => $this->educations,
            'student_status_id' => $this->student_status_id,
            'coordinator_id' => $this->coordinator_id,
            'coordinator_name' => $this->coordinator ? $this->getFullName(
                $this->coordinator->user->first_name ?? "",
                $this->coordinator->user->middle_name ?? "",
                $this->coordinator->user->last_name ?? "",
            ) : null,
            'coordinator_email' => $this->coordinator ? $this->coordinator->user->email : null,
            'coordinator_phone_number' => $this->coordinator ? $this->coordinator->user->phone_number : null,
            'latest_application' => $this->latestApplication,
        ]);
    }
}
