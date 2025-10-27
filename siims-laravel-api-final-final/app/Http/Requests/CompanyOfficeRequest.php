<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyOfficeRequest extends FormRequest
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
            'office_type_id' => 'required|integer',
            'supervisor_id' => 'nullable|integer',
            'name' => 'required|string|min:5',
            'phone_number' => 'nullable|string',
            'street' => 'nullable|string',
            'barangay' => 'nullable|string',
            'city_municipality' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string',
        ];
    }
}
