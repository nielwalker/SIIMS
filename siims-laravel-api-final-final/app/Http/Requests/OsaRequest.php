<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class OsaRequest extends UserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // Get authenticated user
        $authUser = Auth::user();

        // Check if user has this role
        return $authUser->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // Get the rules from User Request
        $rules = parent::rules();

        /**
         * METHOD: PUT
         */
        if($this->isMethod('put')) {

            // * Remvoe 'id, password' attributes

            unset($rules['id']);
            unset($rules['password']);

        }

        // Return rules
        return $rules;
    }
}
