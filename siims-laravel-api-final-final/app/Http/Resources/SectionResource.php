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
        // Note: Sections without coordinators should still be included
        if ($this->relationLoaded('coordinator') && $this->coordinator) {
            // Check if coordinator has user relationship loaded
            if ($this->coordinator->relationLoaded('user') && $this->coordinator->user) {
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
                // Coordinator exists but user not loaded
                $data['coordinator_name'] = null;
                $data['coordinator'] = null;
            }
        } else {
            // Section has no coordinator - this is fine, still include it
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
