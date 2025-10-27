<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkExperienceResource extends BaseResource
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
            "job_position" => $this->job_position,
            "company_name" => $this->company_name,
            "full_address" => $this->full_address,
            "start_date" => $this->start_date,
            "end_date" => $this->end_date,
            "created_at" => $this->formatDateOnlyDate($this->created_at),
            "updated_at" => $this->formatDateOnlyDate($this->updated_at),
        ];
    }




}
