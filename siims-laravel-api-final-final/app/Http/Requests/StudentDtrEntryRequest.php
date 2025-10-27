<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentDtrEntryRequest extends FormRequest
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
            "date" => ['required', 'date'],
            "time_in" => ['required', 'date'],
            "time_out" => ['date', 'nullable'],
            "hours_received" => ['integer', 'nullable'],
        ];
    }
}
