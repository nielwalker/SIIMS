<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoordinatorProfileRequest extends FormRequest
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
            "first_name" => ['required', 'min:3', 'max:255'],
            "middle_name" => ['nullable','min:1', 'max:100'],
            "last_name" => ['required', 'min:3', 'max:255'],
            "email" => ['required', 'min:10', 'max:255', 'email'],
            "gender" => ['nullable', 'in:male,female,other'],
        ];
    }
}
