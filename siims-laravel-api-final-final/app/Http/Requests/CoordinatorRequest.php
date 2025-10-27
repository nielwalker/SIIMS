<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CoordinatorRequest extends UserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get authenticated user
        $user = $this->user();

        // Authorize request if the user has these roles
        return $user->hasRoles(['admin', 'chairperson', 'dean'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get authenticated user
        // ! TBD
        // $user = $this->user();

        // Get the rules from User Request
        $rules = parent::rules();


        /**
         * METHOD: PUT
         * ALLOWED: Admin, Dean, Chairperson
         */
        if ($this->isMethod('put')) {

            //* Remove these attributes if the request is PUT
            unset($rules['id']);
            unset($rules['password']);
        }

        $rules = array_merge($rules, [
            'program_id' => ['required', 'exists:programs,id']
        ]);

        // Return rules
        return  $rules;
    }
}
