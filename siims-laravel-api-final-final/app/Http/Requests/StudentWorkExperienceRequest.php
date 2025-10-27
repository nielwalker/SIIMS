<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentWorkExperienceRequest extends FormRequest
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
            "job_position" => ['required', 'string', 'min:2', 'max:255'],
            "company_name" => ['required', 'string', 'min:2', 'max:255'],
            "full_address" => ['required', 'string', 'min:2', 'max:255'],
            "start_date" => ['required', 'date', 'date_format:Y-m-d H:i:s'],
            "end_date" => ['required', 'date', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
