<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeanResource extends JsonResource
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

        // Remove the roles attribute
        unset($userData['roles']);

        // Add or modify fields specific to the dean
        return array_merge($userData, [
            "college_id" => $this->college->id ?? "No College ID",
            "college_name" => $this->college->name ?? "No College",
        ]);
    }
}
