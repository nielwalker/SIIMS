<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentEndorsementLetterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "description" => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            "student_ids" => [
                'nullable',         // Ensure the field is present and not null
                'array',            // Ensure the input is an array
                'min:1',            // Ensure the array has at least one element
            ],
            "student_ids.*.student_id" => [
                'nullable',     
                'string',     // Ensure each element in the array is an integer
                'exists:students,id', // (Optional) Validate each ID exists in the `students` table
            ],
        ];
    }
}
