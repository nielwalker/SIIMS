<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTrackingResource extends BaseResource
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
            "file_path" => $this->file_path,
            'document_type' => $this->documentType->name,
            "updated_at" => $this->formatDateOnlyDate($this->updated_at),
            'status' => $this->documentStatus->name,
        ];
    }
}
