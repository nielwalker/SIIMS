<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssigningStudentRequest extends FormRequest
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
            'coordinator_id' => ['required', 'exists:coordinators,user_id'],
            'ids' => 'required',
            'ids.*' => 'integer', // Each item in the array should be an integer
        ];
    }
}
