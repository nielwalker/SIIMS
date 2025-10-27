<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WeeklyRecordRequest extends FormRequest
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

        // Initialize rules
        $generalRules = [
            'week_number' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'no_of_hours' => ['required'],
            'learnings' => ['required'],
            'tasks' => ['required'],
        ];

        // Check if HTTP Method is put
        if($this->isMethod('put')) {
            $generalRules['id'] = ['required', 'exists:weekly_entries,id'];
        }

        // Return
        return $generalRules;
    }
}
