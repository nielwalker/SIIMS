<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchEndorseStudentResource extends BaseResource
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
            "fullName" => $this->getFullName(
                $this->user->first_name ?? "",
                $this->user->middle_name ?? "",
                $this->user->last_name ?? "",
            ),
            'phoneNumber' => $this->user->phone_number,
            'email' => $this->user->email,
            'hasOrientation' => $this->program->has_orientation,
        ];
    }
}
