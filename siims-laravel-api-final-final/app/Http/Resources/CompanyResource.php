<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CompanyResource extends JsonResource
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

        // * Remove the 'roles' attribute
        unset($userData['roles']);
       
        // Merged array
        $userData = array_merge($userData, [
            "company_name" => $this->name,
            "website_url" => $this->website_url,
            "logo_url" => $this->logo_url,
            "total_supervisors" => $this->supervisors_count,
            "deleted_at" => $this->deleted_at,
        ]);

        // ! For Dean and Chairperson
        if($user->hasRoles(['dean', 'chairperson'])->exists()) {
            // * Remove deleted_at' attributes
            unset($userData['deleted_at']);
        }

        // Return resources
        return $userData;
    }
}
