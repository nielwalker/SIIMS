<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicantResource extends BaseResource
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
            "office" => $this->workPost->office->name,
            "job_title" => $this->workPost->title,
            "student_id" => $this->student->id,
            "name" => $this->getFullName(
                $this->student->user->first_name ?? "",
                $this->student->user->middle_name ?? "",
                $this->student->user->last_name ?? "",
            ),
            "program" => $this->student->program->name,
            "college" => $this->student->program->college->name,
            "email" => $this->student->user->email,
            "phone_number" => $this->student->user->phone_number,
            "status_id" => $this->application_status_id,
            "status" => $this->applicationStatus->name,
            "applied_at" => $this->formatDateOnlyDate($this->created_at),
            "documents" => $this->documentSubmissions->map(function ($document) {
                return [
                    "id" => $document->id,
                    "name" => $document->documentType->name,
                    "file_path" => $document->file_path,
                ];
            }),
            "company" => $this->workPost->office->company->name,
        ];
    }
}
