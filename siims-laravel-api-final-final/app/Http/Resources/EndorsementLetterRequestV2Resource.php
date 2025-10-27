<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndorsementLetterRequestV2Resource extends BaseResource
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
            'student_id' => $this->student->user->id,
            "name" => $this->getFullName(
                $this->student->user->first_name ?? "",
                $this->student->user->middle_name ?? "",
                $this->student->user->last_name ?? "",
            ),
            'program' => $this->student->program->name,
            'college' => $this->student->program->college->name,
            'endorse_students' => $this->endorseStudents->map(function ($endorse_student) {

                $user = $endorse_student->student->user;

                return [
                    "student_id" => $user->id,
                    'full_name' => $this->getFullName(
                        $user->first_name ?? "",
                        $user->middle_name ?? "",
                        $user->last_name ?? "",
                    ),
                    'email' => $user->email,
                    'phone_number' => $user->phone_number
                ];
            }),
            'created_at' => $this->formatDateOnlyDate($this->created_at),
        ];

        // return parent::toArray($request);
    }
}
