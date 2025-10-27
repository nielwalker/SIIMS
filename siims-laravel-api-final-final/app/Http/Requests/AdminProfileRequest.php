<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // General attributes
        $attributes = [
            "college_id" => ['required', 'integer'],
            "name" => ['required', 'string', 'min:3', 'max:255'],
        ];

        // For Put
        if($this->isMethod('put')) {
            $attributes['chairperson_id'] = ['integer', 'nullable'];
        }

        return $attributes;
    }
}
