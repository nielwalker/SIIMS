<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class DocumentSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get authenticated user
        $authUser = Auth::user();

        // General resource
        $resource = [
            "id" => $this->id,
            "name" => $this->documentType->name,
            "status_id" => $this->document_status_id,
            "status" => $this->documentStatus->name,
            "file_path" => $this->file_path,
            "step" => $this->documentType->documentStep->id,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];

        // Determine if the user can update the document
        $canUpdate = false;

        // Add condition: If the document_status_id is 4 (Approved), updates are not allowed
        if ($this->document_status_id !== 4) {
            if ($authUser->hasRole('company') && $this->documentType->documentStep->id === 1) {
                $canUpdate = true;
            } elseif ($authUser->hasRole('osa') && $this->documentType->documentStep->id === 2) {
                $canUpdate = true;
            }
        }

        $resource['can_update'] = $canUpdate;

        return $resource;
    }
}
