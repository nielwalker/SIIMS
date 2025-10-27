<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorRequest extends UserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get authenticated user
        $user = $this->user();

        // Authorize request if the user has these roles
        return $user->hasRoles(['admin', 'company'])->exists();
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


        // Merge array
        $rules = array_merge($rules, [
            'office_id' => ['required', 'integer', 'exists:offices,id']
        ]);

        /**
         * METHOD: PUT
         */
        if ($this->isMethod('put')) {

            //* Remove these attributes if the request is PUT
            unset($rules['id']);
            unset($rules['password']);
        }

        // Return rules
        return $rules;
    }
}
