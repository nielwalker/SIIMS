<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LatestApplicationResource extends BaseResource
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
            "work_post_id" => $this->work_post_id,
            "title" => $this->workPost->title,
            'work_duration' => $this->workPost->work_duration,
            'office' => $this->workPost->office->name,
            'company_id' => $this->workPost->office->company->id,
            'company' => $this->workPost->office->company->name,
            'profile_url' => $this->workPost->office->company->user->profile_image_url,
            "application_status_id" => $this->application_status_id,
            'applied_date' => $this->formatDateOnlyDate($this->applied_date),
            
        ];  

        // return parent::toArray($request);
    }
}
