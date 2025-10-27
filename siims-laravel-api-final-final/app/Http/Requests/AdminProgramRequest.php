<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminProgramRequest extends FormRequest
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
        // General Attributes
        $attributes = [
            "college_id" => ['required', 'integer'],
            "name" => ['required', 'min:3', 'max:255', 'unique:programs,name']
        ];

        // For Put method
        if($this->isMethod('put')) {
            $attributes['chairperson_id'] = ['nullable', 'integer'];
        }

        return $attributes;
    }
}
