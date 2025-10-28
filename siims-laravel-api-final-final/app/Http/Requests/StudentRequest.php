<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentRequest extends UserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {   

        // Get Auth User
        $user = $this->user();

        // Allow request if the user has these roles
        return $user->hasRoles(['admin', 'chairperson', 'dean'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get the rules from User Request
        $rules = parent::rules();

        // Merged array of rules
        $rules = array_merge($rules,[
            "age" => ['nullable', 'integer'],
            "date_of_birth" => ['nullable', 'date'],
            "program_id" => ['required', 'integer', 'exists:programs,id'],
            "coordinator_id" => ['required', 'string', 'exists:coordinators,id'],
            // Optional company assignment at creation/update
            "company_id" => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        /**
         * METHOD: PUT
         * ALLOWED: Admin
         */
        if ($this->isMethod('put') && $this->user()->hasRole('admin')) {

            //* Remove these attributes if the request is PUT
            unset($rules['id']);
            unset($rules['password']);
        }

        // Return rules
        return $rules;
    }
}
