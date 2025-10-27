<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends BaseResource
{
    
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "roles" => $this->roles,
            "first_name" => $this->first_name ?? '',
            "middle_name" => $this->middle_name ?? "",
            "last_name" => $this->last_name ?? "",
            "email" => $this->email,
            "email_verified_at" => $this->formatDate($this->email_verified_at),
            "gender" => $this->gender,
            "phone_number" => $this->phone_number,
            "street" => $this->street,
            "barangay" => $this->barangay,
            "city_municipality" => $this->city_municipality,
            "province" => $this->province,
            "postal_code" => $this->postal_code,
            "profile_image_url" => $this->profile_image_url,
            "cover_image_url" => $this->cover_image_url,
            "created_at" => $this->formatDate($this->created_at),
            "updated_at" => $this->formatDate($this->updated_at),
            "deleted_at" => $this->deleted_at ? $this->formatDate($this->deleted_at) : $this->deleted_at,
        ];
    }
}
