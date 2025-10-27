<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminChairpersonRequest extends FormRequest
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
        // General Rules
        $general_rules = [
            'id' => ['required', 'min:9', 'max:12'],
            'program_id' => ['required', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'gender' => ['nullable', 'in:male,female,other'],
            'phone_number' => ['nullable', 'string'],
            'street' => ['nullable', 'string'],
            'barangay' => ['nullable', 'string'],
            'city_municipality' => ['nullable', 'string'],
            'province' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
        ];

        // Post Method
        if ($this->isMethod('post')) {
            // Password
            $general_rules['password'] = ['required', 'string', 'min:8', 'max:128'];
        }

       return $general_rules;
    }
}
