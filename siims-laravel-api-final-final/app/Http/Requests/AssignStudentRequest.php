<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get auth user
        $user = $this->user();

        // Allow authorization if the user has these roles
        return $user->hasRoles(['admin', 'chairperson'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // This is for assigning the student to coordinator validation
        return [
            "coordinator_id" => ['required', 'integer', 'exists:coordinators,id'],
            "student_ids" => [
                'required',         // Ensure the field is present and not null
                'array',            // Ensure the input is an array
                'min:1',            // Ensure the array has at least one element
            ],
            "student_ids.*.student_id" => [
                'required',     
                'integer',     // Ensure each element in the array is an integer
                'exists:students,id', // (Optional) Validate each ID exists in the `students` table
            ],
        ];
    }
}
