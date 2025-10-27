<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class WorkExperienceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the authenticated user is either an admin or student based on roles
        $user = Auth::user();
        // Check if the user has either the 'admin' or 'student' role
        return $user->hasRole(roleName: 'admin') || $user->hasRole(roleName: 'student');
    }   

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'job_position' => ['required','string', 'min:5', 'max:50'],
            'company_name' => [ 'required' ,'string', 'min:5', 'max:50'],
            'full_address' => [ 'required', 'string', 'min:5', 'max:100'],
            'start_date' => ['required', 'date', 'string', 'max:20'],
            'end_date' => ['required', 'date', 'string', 'max:20'],
        ];
    }
}
