<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CoordinatorResource extends BaseResource
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

        // ! For Admin (and Chairperson acting as admin)
        if ($user->hasRole('admin') || $user->hasRole('chairperson')) {

            // * Added 'college' attribute
            $userData = array_merge($userData, [
                'college' => $this->program->college->name ?? "No College",
                'program' => $this->program->name ?? "No Program",
                'program_id' => $this->program->id ?? "No Program ID",
            ]);
        }

        // ! For Dean
        else if ($user->hasRole('dean')) {

            // * Remove 'email_verified_at, deleted_at' attributes
            unset($userData['email_verified_at']);
            unset($userData['deleted_at']);

            // * Added 'program_id, program' attribute
            $userData = array_merge($userData, [
                'program_id' => $this->program->id ?? "No Program ID",
                "program" => $this->program->name ?? "No Program",
            ]);
        }

        // Add or modify fields specific to the coordinator
        return array_merge($userData, [
            'total_students' => $this->students_count,
            'deleted_at' => $this->formatDateOnlyDate($this->deleted_at),
        ]);
    }
}
