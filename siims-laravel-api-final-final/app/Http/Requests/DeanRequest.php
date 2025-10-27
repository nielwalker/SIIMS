<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class DeanRequest extends UserRequest
{


    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get authenticated user
        $user = $this->user();

        return $user->hasRoles(['admin', 'dean'])->exists();
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
                "college_id" => ['required', 'exists:colleges,id']
            ]);
        }

        /**
         * METHOD: PUT
         */
        else if ($this->isMethod('put')) {

            unset($rules['id']);
            unset($rules['password']);

            // Add new validation rule
            $rules = array_merge($rules, [
                "college_id" => ['required', 'exists:colleges,id'],
                'email' => ['nullable']
            ]);
        }

        // Return Rules
        return $rules;
    }
}
