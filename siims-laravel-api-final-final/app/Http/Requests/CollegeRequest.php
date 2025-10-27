<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CollegeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get Auth User
        $user = Auth::user();

        // Check if the User has these Roles
        return $user->hasRoles(['admin', 'dean'])->exists();
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
            'name' => ['required', 'string', 'min:10', 'max:100', 'unique:colleges,name'],
        ];

        // PUT
        if($this->isMethod('put')) {
            $general_rules['name'] = ['required', 'string', 'min:10', 'max:100'];
            $general_rules['dean_id'] = ['nullable', 'exists:users,id'];
        }

        // Return general rules
        return $general_rules;
    }
}
