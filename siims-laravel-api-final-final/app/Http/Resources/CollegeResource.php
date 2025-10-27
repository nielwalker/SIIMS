<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollegeResource extends JsonResource
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
            "dean_id" => $this->dean_id ?? "No ID",
            "name" => $this->name,
            "dean_name" => $this->dean ? trim($this->dean->first_name . ' ' . $this->dean->middle_name . ' ' . $this->dean->last_name) : "No Dean assigned",
            "email" => $this->dean ? $this->dean->email : "No email",
            "created_at" => $this->formatDate($this->created_at),
            "updated_at" => $this->formatDate($this->updated_at),
            "deleted_at" => $this->formatDate($this->deleted_at),
          ];
    }

    /**
     * Custom method to format date.
     *
     * @param mixed $date
     * @return string
     */
    private function formatDate($date) {
        // If date is not null, format it using Carbon
        return $date ? Carbon::parse($date)->format('F j, Y, g:i a') : null;
    }
}
