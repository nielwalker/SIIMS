<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorWorkPostRequest extends FormRequest
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

        // General Rules
        $general_rules = [
            "work_type_id" => ["required", 'integer'],
            "title" => ['required', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'string'],
            'max_applicants' => ['required', 'integer'],
            'start_date' => ['date', 'required'], 
            'end_date' => ['date', 'required'],
            'work_duration' => ['nullable', 'string'] 
        ];

        return $general_rules;
    }
}
