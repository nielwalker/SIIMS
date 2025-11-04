<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoordinatorProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle null user relationship safely
        if (!$this->user) {
            return [
                'id' => $this->id,
                'first_name' => null,
                'middle_name' => null,
                'last_name' => null,
                'email' => null,
                'gender' => null,
                'phone_number' => null,
                'street' => null,
                'barangay' => null,
                'city_municipality' => null,
                'province' => null,
                'postal_code' => null,
                'profile_image_url' => null,
                'cover_image_url' => null,
                'college' => null,
                'college_name' => null,
                'program' => null,
            ];
        }

        // Use the UserResource to structure the base user data
        $userData = (new UserResource($this->user))->toArray($request);

        // Remove the roles attribute
        unset($userData['roles']);

        // Add or modify fields specific to the coordinator
        // Handle null program and college relationships safely
        $college = null;
        $program = null;
        
        if ($this->program) {
            $program = $this->program->name ?? null;
            if ($this->program->college) {
                $college = $this->program->college->name ?? null;
            }
        }

        return array_merge($userData, [
            'college' => $college,
            'college_name' => $college, // Also include college_name for compatibility
            'program' => $program,
        ]);
    }
}
