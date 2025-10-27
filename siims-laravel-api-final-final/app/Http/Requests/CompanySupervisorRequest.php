<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanySupervisorRequest extends FormRequest
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
            "first_name" => ['required', 'string', 'max:255'],
            "middle_name" => ['nullable', 'string', 'max:255'],
            "last_name" => ['required', 'string', 'min:3', 'max:255'],
            "email" => ['required', 'string', 'email', 'min:4', 'max:50'],
            'gender' => ['required'],
            'phone_number' => ['nullable', 'string', 'max:12'],
            'street' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city_municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'office_id' => ['required', 'integer'],
        ];
        
        // Method: Post
        if($this->isMethod('post')) {
            $general_rules['id'] = ['required', 'integer', 'min:10'];
            $general_rules['password'] = ['required', 'string', 'min:8'];
        }

        return $general_rules;
    }
}
