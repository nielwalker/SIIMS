<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobPostingResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // General Attributes
        $generalAttributes = [
            "id" => $this->id,
            "work_type" => ucfirst($this->workType->name),
            'title' => $this->title,
            "responsibilities" => $this->responsibilities,
            "qualifications" => $this->qualifications,
            "max_applicants" => $this->max_applicants,
            "start_date"=> $this->formatDateOnlyDate($this->start_date),
            "end_date"=> $this->formatDateOnlyDate($this->end_date),
            "work_duration"=> $this->work_duration,
            "company_id" => $this->office->company->id,
            "company_name" => $this->office->company->name,
            "company_profile" => $this->office->company->user->profile_image_url,
            "office_name" => $this->office->name,
            "street"=> $this->office->street,
            "barangay"=> $this->office->barangay,
            "city_municipality"=> $this->office->city_municipality,
            "province"=> $this->office->province,
            "postal_code"=> $this->office->postal_code,
            "is_closed" => Carbon::now()->greaterThan($this->end_date),
        ];

        return $generalAttributes;
    }
}
