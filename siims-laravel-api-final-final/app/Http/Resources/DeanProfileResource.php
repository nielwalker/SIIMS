<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeanProfileResource extends ProfileResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseData = parent::toArray($request);

        // Add or modify fields specific to the dean
        return array_merge($baseData, [
            "college_id" => $this->college->id ?? "No College ID",
            "college_name" => $this->college->name ?? "No College",
        ]);
    }
}
