<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class WeeklyEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // Get auth User
        $user = Auth::user();

        return $user->hasRoles(['admin', 'student'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "week_number" => ['required', 'integer'],
            "start_date" => ['required', 'date'],
            "end_date" => ['required', 'date'],
            "tasks" => ['required', 'string', 'min:10'],
            'learnings' => ['required', 'string', 'min:10'],
            'no_of_hours' => ['required', 'integer'],
        ];
    }
}
