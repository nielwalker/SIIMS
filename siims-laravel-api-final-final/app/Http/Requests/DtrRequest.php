<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class DtrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {   
        // Get User
        $user = Auth::user();
        
        // Authorize the User who has these roles
        return $user->hasRoles(['admin', 'student', 'supervisor'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "date" => ['required', 'date',],
            "time_in" => ['required', 'string'],
            "time_out" => ['nullable', 'string'],
            "hours_received" => ['required', 'integer'], 
        ];
    }
}
