<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChairpersonStudentRequest extends FormRequest
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

        if ($this->isMethod('post')) {
            return [
                'id' => 'required|min:11',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email',
                'password' => 'required|string|min:8',
                'gender' => 'required|in:male,female,other',
                'phone_number' => 'nullable|string',
                'street' => 'nullable|string',
                'barangay' => 'nullable|string',
                'city_municipality' => 'nullable|string',
                'province' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'program_id' => 'required|integer',
                'coordinator_id' => 'nullable|integer',
                'age' => 'nullable|integer',
                'date_of_birth' => 'nullable|date',
            ];
        }

        // This is for assigning the student to coordinator validation
        return [
            "coordinator_id" => ['required', 'integer'],
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
