<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualEndorsementLetterRequests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // Get auth user
        $user = $this->user();

        // Return authorization

        return $user->hasRoles(['admin', 'chairperson'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // General rules 
        $generalRules = [
            "requested_by_id" => ['required', 'integer', 'exists:students,id'],
            "company_name" => ['required', 'string'],
            'company_address' => ['required', 'string'],
            'recipient_name' => ['required', 'string'],
            'recipient_position' => ['required', 'string'],
        ];

        // Merge rules
        $generalRules = array_merge($generalRules, $this->studentIDsRules());

        // Return rules
        return $generalRules;
        
        // Return rules
        // return $this->studentIDsRules();
    }

    /**
     * Summary of studentIDsRules: A public function that returns the student IDs Rules
     * @return array
     */
    public static function studentIDsRules(): array
    {
        return [
            "student_ids" => [
                'nullable',         // Ensure the field is present and not null
                'array',            // Ensure the input is an array
                'min:0',            // Ensure the array has at least one element
            ],
            "student_ids.*.student_id" => [
                'nullable',
                'exists:students,id', // (Optional) Validate each ID exists in the `students` table
            ],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
