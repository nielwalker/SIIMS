<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminStudentRequest extends FormRequest
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

        // Method: POST
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

        return [
            //
        ];
    }
}
