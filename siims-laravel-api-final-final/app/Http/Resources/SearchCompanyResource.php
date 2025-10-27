<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchCompanyResource extends BaseResource
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
            "name" => $this->name,
            
            "fullName" => $this->getFullName(
                $this->user->first_name ?? "",
                $this->user->middle_name ?? "",
                $this->user->last_name ?? "",
            ),
            "fullAddress" => $this->getFullAddress(
        $this->user->street ?? "",
        $this->user->barangay ?? "",
        $this->user->city ?? "",
        $this->user->province ?? "",
        $this->user->postal_code ?? "",
    ),
            'phoneNumber' => $this->user->phone_number,
            'email' => $this->user->email,
        ];
    }
}
