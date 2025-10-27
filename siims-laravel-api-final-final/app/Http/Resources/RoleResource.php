<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => ucwords($this->name),
            'created_at' => $this->formatDate($this->created_at),
            // 'updated_at' => $this->formatDate($this->updated_at),
            // 'deleted_at' => $this->formatDate($this->deleted_at),
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
