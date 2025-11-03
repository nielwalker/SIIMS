<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        
        // Add coordinator information if loaded
        if ($this->relationLoaded('coordinator') && $this->coordinator) {
            $data['coordinator_name'] = trim(
                ($this->coordinator->user->first_name ?? '') . ' ' .
                ($this->coordinator->user->middle_name ?? '') . ' ' .
                ($this->coordinator->user->last_name ?? '')
            );
            $data['coordinator'] = [
                'id' => $this->coordinator->id,
                'name' => trim(
                    ($this->coordinator->user->first_name ?? '') . ' ' .
                    ($this->coordinator->user->middle_name ?? '') . ' ' .
                    ($this->coordinator->user->last_name ?? '')
                ),
            ];
        } else {
            $data['coordinator_name'] = null;
            $data['coordinator'] = null;
        }
        
        // Add program information if loaded
        if ($this->relationLoaded('program') && $this->program) {
            $data['program_name'] = $this->program->name ?? null;
        } else {
            $data['program_name'] = null;
        }
        
        return $data;
    }
}
