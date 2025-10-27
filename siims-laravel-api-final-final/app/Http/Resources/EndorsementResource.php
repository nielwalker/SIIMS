<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndorsementResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // General resources
        $general = [
            "id" => $this->id,
            "work_post" => $this->workPost->title,
            'office' => $this->workPost->office->name,
            'company' => $this->workPost->office->company->name,
            'file_path' => $this->endorsement_letter_request_status_id === 3 ? $this->endorsement_file : null,
            'remarks' => $this->remarks,
            'status_id' => $this->endorsement_letter_request_status_id,
            'status' => $this->endorsementLetterRequestStatus->name,
            'created_at' => $this->formatDateOnlyDate($this->created_at),
        ];

        // Return resources
        return $general;

        // return parent::toArray($request);
    }
}
