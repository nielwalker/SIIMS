<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChairpersonRequest extends UserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get authenticated user
        $user = $this->user();

        return $user->hasRoles(['admin', 'chairperson'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get authenticated user
        $user = $this->user();

        // Get the rules from User Request
        $rules = parent::rules();

        /**
         * METHOD: POST
         * ALLOWED: Admin
         */
        if ($this->isMethod('post') && $user->hasRole('admin')) {

            $rules = array_merge($rules, [
                'program_id' => ['required', 'exists:programs,id'],
                "allow_coordinator" => ['required', 'boolean'],
            ]);
        }

        /**
         * METHOD: PUT
         * ALLOWED: Admin
         */
        else if($this->isMethod('put') && $user->hasRole('admin')) {

            unset($rules['id']);
            unset($rules['password']);
            
            // Add new validation rule
            $rules = array_merge($rules, [
                'email' => ['required', 'email']
            ]);

        }

        // Return rules
        return $rules;
    }
}
