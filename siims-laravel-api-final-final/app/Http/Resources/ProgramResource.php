<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProgramResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // Get Auth User
        $user = Auth::user();

        // General Attributes
        $generalAttributes = [
            'id' => $this->id,
            'chairperson_id' => $this->chairperson ? $this->chairperson_id : "No ID",
            'name' => $this->name,
        ];

        /**
         * Check if User has a Role of Admin
         */
        if ($user->hasRole('admin')) {
            $generalAttributes['total_students'] = $this->students_count ?? 0;
            $generalAttributes['chairperson_assigned'] = $this->chairperson ? $this->getFullName($this->chairperson->first_name ?? "", $this->chairperson->middle_name ?? "", $this->chairperson->last_name ?? "") : "No Chairperson assigned";
            $generalAttributes['email'] = $this->chairperson ? $this->chairperson->email : "No Email";
            $generalAttributes['phone_number'] = $this->chairperson ? $this->chairperson->phone_number : "No Phone Number";
            $generalAttributes['college_id'] = $this->college ? $this->college->id : "No College ID";
            $generalAttributes['college'] = $this->college ? $this->college->name : "No College name";
            $generalAttributes['created_at'] = $this->formatDate($this->created_at);
            $generalAttributes['updated_at'] = $this->formatDate($this->updated_at);
            $generalAttributes['deleted_at'] = $this->formatDate($this->deleted_at);
        } else if ($user->hasRole('dean')) {
            $generalAttributes['total_students'] = $this->students_count ?? 0;
            $generalAttributes['total_coordinators'] = $this->coordinators_count ?? 0;
            $generalAttributes['chairperson_assigned'] = $this->chairperson ? $this->getFullName($this->chairperson->first_name ?? "", $this->chairperson->middle_name ?? "", $this->chairperson->last_name ?? "") : "No Chairperson assigned";
            $generalAttributes['email'] = $this->chairperson ? $this->chairperson->email : "No Email";
            $generalAttributes['phone_number'] = $this->chairperson ? $this->chairperson->phone_number : "No Phone Number";
            $generalAttributes['created_at'] = $this->formatDate($this->created_at);
            $generalAttributes['updated_at'] = $this->formatDate($this->updated_at);
        }

        // Return Attributes
        return $generalAttributes;
    }
}
