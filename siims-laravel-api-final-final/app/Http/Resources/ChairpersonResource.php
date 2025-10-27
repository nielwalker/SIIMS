<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChairpersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // Use the UserResource to structure the base user data
        $userData = (new UserResource($this))->toArray($request);

        // * Remove other attributes
        unset($userData['roles']);

        // Add or modify fields specific to the dean
        return array_merge($userData, [
            'program_id' => $this->program->id ?? "No Program ID",
            'program' => $this->program->name ?? "No Program",
            'college' => $this->program->college->name ?? "No College",
        ]);
    }
}
