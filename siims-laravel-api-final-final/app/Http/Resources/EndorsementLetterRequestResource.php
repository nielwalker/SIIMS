<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EndorsementLetterRequestResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // Get authenticated user
        /**
         * @var User
         */
        $authUser = Auth::user();

        // General attributes
        $generalAttributes = [
            "id" => $this->id,
            "name" => $this->student->user ? $this->getFullName(
                $this->student->user->first_name ?? "",
                $this->student->user->middle_name ?? "",
                $this->student->user->last_name ?? ""
            ) : "", // Return empty string if 'user' is null
            "email" => $this->student->user->email,
            "description" => $this->description,
            "student_id" => $this->student->id,
            "job_title" => $this->workPost ? $this->workPost->title : null,
            "endorse_students_count" => $this->endorse_students_count,
            "file_path" => $this->endorsement_file,
            "letter_status_id" => $this->endorsementLetterRequestStatus->id,
            "letter_status_name" => $this->endorsementLetterRequestStatus->name,
            "status" => $this->status->name,
            "remarks" => $this->remarks,
            "students" => $this->endorseStudents ? $this->endorseStudents : null,
        ];

        // ! FOR DEAN
        if ($authUser->hasRole('dean')) {

            // Append Program attribute program come from
            $generalAttributes['program'] = $this->student->program->name;
        }


        // Check if status is (walk-in)
        if (strtolower($request['status']) === 'walk-in') {

            // Merge this attributes
            $generalAttributes = array_merge($generalAttributes, [
                "company_address" => $this->company_address,
                "company_name" => $this->company_name,
                "recipient_name" => $this->recipient_name,
                "recipient_position" => $this->recipient_position,

            ]);
        }

        /**
         * If status is archived then fetch this.
         */
        if($request->query('status') === 'archived') {
            // Merge this attribute
            $generalAttributes = array_merge($generalAttributes, [
                'deleted_at' => $this->formatDate($this->deleted_at),
            ]);
        }

        // Return attributes
        return $generalAttributes;
    }
}
