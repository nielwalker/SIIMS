<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProfileResource extends JsonResource
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

        // Add or modify fields specific to the dean
        return array_merge($userData, [
            "name" => $this->name,
            "website_url" => $this->website_url,
            "logo_url" => $this->logo_url,
        ]);
    }
}
