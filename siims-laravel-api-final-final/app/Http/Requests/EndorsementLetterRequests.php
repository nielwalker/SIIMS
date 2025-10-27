<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class EndorsementLetterRequests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get auth User
        $user = $this->user();

        return $user->hasRoles(['admin', 'student'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // Create general rules
        $generalRules = [
            "application_id" => ['required', 'string', 'exists:applications,id'],
            "description" => ['required', 'min:10', 'max:255'],
            "work_post_id" => ['required', 'exists:work_posts,id']
        ];

        // Merge array of rules
        $generalRules = array_merge(ManualEndorsementLetterRequests::studentIDsRules(), $generalRules);

        // Return rules
        return $generalRules;

       /*  return [
            "application_id" => ['required', 'string', 'exists:applications,id'],
            
            "description" => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            "student_ids" => [
                'nullable',         // Ensure the field is present and not null
                'array',            // Ensure the input is an array
                'min:0',            // Ensure the array has at least one element
            ],
            "student_ids.*.student_id" => [
                'nullable',
                'string',     // Ensure each element in the array is an integer
                'exists:students,id', // (Optional) Validate each ID exists in the `students` table
            ],
        ]; */
    }
}
