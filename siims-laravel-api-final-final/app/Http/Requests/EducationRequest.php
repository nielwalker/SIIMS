<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EducationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user->hasRole('student');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "school_name" => ['required', 'min:3', 'max:100', 'string'],
            "full_address" => ['required', 'min:3', 'max:150', 'string'],
            "start_date" => ['required', 'min:3', 'date',],
            "end_date" => ['required', 'min:3', 'date'],
        ];
    }
}
