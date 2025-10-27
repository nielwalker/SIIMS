<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class DailyRecordRequest extends FormRequest
{

    /**
     * Summary of authUser
     * @var User
     */
    private $authUser;

    public function __construct()
    {
        $this->authUser = Auth::user();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Return roles
        return $this->authUser->hasRoles(['admin', 'student'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // General rules
        $general_rules = [
            'date' => ['required', 'date'],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['nullable', 'date_format:H:i', 'after:time_in'],
            'hours_received' => ['required'],
        ];

        // Check if Admin
        if ($this->authUser->hasRole('admin')) {

            // Merge
            $general_rules = array_merge($general_rules, [
                'student_id' => ['required', 'exists:students,user_id']
            ]);
        }

        // Check if request method is put
        if ($this->isMethod('put')) {

            // Merge array
            $general_rules = array_merge($general_rules, [
                'id' => ['required', 'exists:dtr_entries,id']
            ]);
        }

        // Return
        return $general_rules;
    }
}
