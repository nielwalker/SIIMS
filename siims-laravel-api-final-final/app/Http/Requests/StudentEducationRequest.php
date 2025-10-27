<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentEducationRequest extends FormRequest
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
            "school_name" => ['required', 'min:3', 'max:255', 'string'],
            "full_address" => ['required', 'min:3', 'max:255', 'string'],
            "start_date" => ['required', 'date', 'date_format:Y-m-d H:i:s'],
            "end_date" => ['required', 'date', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
