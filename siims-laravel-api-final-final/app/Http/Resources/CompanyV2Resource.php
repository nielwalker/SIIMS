<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyV2Resource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // Get status (request)
        $status = (string) $request->input('status');

        // Use the UserResource to structure the base user data
        $userData = (new UserResource($this->user))->toArray($request);

        // * Remove the 'roles' attribute
        unset($userData['roles']);

        // Merged array
        $userData = array_merge($userData, [
            "company_name" => $this->name,
            "website_url" => $this->website_url,
            "logo_url" => $this->logo_url,
            "total_supervisors" => $this->supervisors_count,
            "deleted_at" => $this->formatDateOnlyDate($this->deleted_at),
        ]);

        // Check if status (request) value
        if($status !== 'archived') {
            unset($userData['deleted_at']);
        }

        // Return resources
        return $userData;
    }
}
