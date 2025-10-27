<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SupervisorResource extends JsonResource
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

        // * Remove 'roles, deleted_at' attribute
        unset($userData['roles']);
        unset($userData['deleted_at']);

        // * Added 'office, company' attribute
        $userData = array_merge($userData, [
            'office_id' => $this->office ? $this->office->id : "",
            'office' => $this->office ? $this->office->name : "",
        ]);

        // ! FOR ADMIN
        if($user->hasRole('admin')) {

            // * Added 'company' attribute
            $userData = array_merge($userData, [
                'company' => $this->company->name,
                'deleted_at' => $this->deleted_at,
            ]);
        }

        // Return resources
        return $userData;
    }
}
