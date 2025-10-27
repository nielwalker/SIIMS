<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends BaseResource
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
            "name" => $this->name,
            "phone_number" => $this->phone_number,
            "street" => $this->street,
            "barangay" => $this->barangay,
            "city_municipality" => $this->city_municipality,
            "province" => $this->province,
            "postal_code" => $this->postal_code,
            "total_work_posts" => $this->work_posts_count,
            "office_type" => $this->officeType ? $this->officeType->name : "",
            'supervisor_id' => $this->supervisor ? $this->supervisor->id : "", 
            'supervisor_name' => $this->supervisor ? $this->getFullName(
                $this->supervisor->user->first_name ?? "",
                $this->supervisor->user->middle_name ?? "",
                $this->supervisor->user->last_name ?? "",
            ) : "", 
            'created_at' => $this->formatDateOnlyDate($this->created_at),
            'updated_at' => $this->formatDateOnlyDate($this->updated_at),
            'deleted_at' => $this->formatDateOnlyDate($this->deleted_at),
        ];

     // return parent::toArray($request);
    }
}
