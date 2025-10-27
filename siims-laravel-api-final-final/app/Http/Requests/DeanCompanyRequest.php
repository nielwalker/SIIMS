<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeanCompanyRequest extends FormRequest
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
            'id' => 'required|integer',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:100',
            'password' => 'required|max:128',
            'gender' => 'nullable|string|max:10',
            'phone_number' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'city_municipality' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'company_name' => 'required|string|max:255',
            'website_url' => 'nullable|string|max:255',
        ];
    }
}
