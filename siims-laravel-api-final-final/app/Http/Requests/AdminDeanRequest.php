<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminDeanRequest extends FormRequest
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
            "first_name" => ['required', 'string', 'min:3'],
            "middle_name" => ['nullable', 'string'],
            "last_name" => ['string', 'min:3'],
            "email" => ['string'],
            "gender" => ['nullable', 'in:male,female,other'],
            'phone_number' => ['nullable','string'],
            'street' => ['nullable','string'],
            'barangay' => ['nullable','string'],
            'city_municipality' => ['nullable','string'],
            'province' => ['nullable','string'],
            'postal_code' => ['nullable','string'],
            'college_id' => ['required', 'integer']
        ];

        // Post Method
        if($this->isMethod('post')) {
            // ID
            $general_rules['id'] = ['required', 'integer', 'min:10'];
            // Password
            $general_rules['password'] = ['required', 'string', 'min:8', 'max:128'];
            // Email Override
            $general_rules['email'] = ['required', 'string', 'email', 'min:3', 'max:64'];
        }

        return $general_rules;
    }
}
