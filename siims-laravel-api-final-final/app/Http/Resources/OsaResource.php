<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OsaResource extends JsonResource
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

        // Return resources
        return $userData;
    }
}
