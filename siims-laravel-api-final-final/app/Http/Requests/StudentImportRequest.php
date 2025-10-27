<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentImportRequest extends FormRequest
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
            'file' => [
                'required', // File is mandatory
                'file', // Must be a valid file
                'mimes:csv,xlsx,xls', // Only allow specific file types
                'max:5120', // Maximum file size in kilobytes (e.g., 5MB)
            ],
            'status' => [
                'required', // Ensure status is provided
                'string', // Must be a string
                'in:ImportAllStudents,ImportAssignCoordinators,AddNewStudent', // Allow only specific values
            ],
            'program_id' => ['required', 'exists:programs,id'],
            'coordinator_id' => ['required', 'exists:coordinators,user_id'],
        ];
    }
}
