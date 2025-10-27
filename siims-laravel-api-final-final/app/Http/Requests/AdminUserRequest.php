<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserRequest extends FormRequest
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
        // General rules
        $general_rules = [
            "id" => ['required', 'integer', 'min:10'],
            "first_name" => ['nullable', 'string', 'max:255'],
            "middle_name" => ['nullable', 'string', 'max:255'],
            "last_name" => ['nullable', 'string', 'min:3', 'max:255'],
            "email" => ['required', 'string', 'email', 'min:4', 'max:50', 'unique:users,email'],
            'gender' => ['nullable'],
            'phone_number' => ['nullable', 'string', 'max:12'],
            'street' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city_municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
        ];

        // For POST requests
        if ($this->isMethod('post')) {
            $general_rules['password'] = ['required', 'string', 'min:8', 'max:128'];
            $general_rules['roles'] = ['required', 'array'];
            $general_rules['roles.*.id'] = ['required', 'integer', 'exists:roles,id']; // Validate each role ID
        }

        // Handle role-specific validation
        if ($this->has('roles')) {
            foreach ($this->roles as $role) {
                switch ($role['id']) {
                    case 1: // Admin
                        break;

                    case 2: // Chairperson
                    case 3: // Coordinator
                        $general_rules['program_id'] = ['required', 'integer', 'exists:programs,id'];
                        break;

                    case 4: // Company
                        $general_rules['company_name'] = ['required', 'string', 'min:1', 'max:255'];
                        $general_rules['website_url'] = ['nullable', 'string', 'min:1', 'max:255'];
                        break;

                    case 5: // Dean
                        $general_rules['college_id'] = ['required', 'integer', 'exists:colleges,id'];
                        break;

                    case 6: // OSA
                        break;

                    case 7: // Student
                        $general_rules['program_id'] = ['required', 'integer', 'exists:programs,id'];
                        $general_rules['coordinator_id'] = ['nullable', 'integer', 'exists:users,id'];
                        $general_rules['age'] = ['nullable', 'integer', 'min:0'];
                        $general_rules['date_of_birth'] = ['nullable', 'date'];
                        break;

                    case 8: // Supervisor
                        $general_rules['office_id'] = ['required', 'integer', 'exists:offices,id'];
                        break;

                    default:
                        break;
                }
            }
        }

        // Return the validation rules
        return $general_rules;
    }
}
