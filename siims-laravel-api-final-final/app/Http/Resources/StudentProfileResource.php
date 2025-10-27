<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends BaseResource
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
            'certificates' => $this->certificates,
            'about_me' => $this->about_me,
            'date_of_birth' => $this->date_of_birth,
            'college' => $this->program->college->name,
            'program' => $this->program->name,
            'age' => $this->age,
            'coordinator_name' => $this->coordinator ? $this->getFullName(
                $this->coordinator->user->first_name ?? "",
                $this->coordinator->user->middle_name ?? "",
                $this->coordinator->user->last_name ?? "",
            ) : null,
            'coordinator_email' => $this->coordinator ? $this->coordinator->user->email : null,
            'coordinator_phone_number' => $this->coordinator ? $this->coordinator->user->phone_number : null
        ]);
    }
}
