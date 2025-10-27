<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DtrResource extends JsonResource
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
            "date" => $this->date,
            "time_in" => $this->time_in,
            "time_out" => $this->time_out,
            "hours_received" => $this->hours_received,
            "status_id" => $this->status->id ?? "No Status ID",
            "status_name" => $this->status->name ?? "No Status",
        ];
    }
}
