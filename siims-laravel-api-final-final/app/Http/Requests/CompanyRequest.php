<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends UserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get Auth user
        $user = $this->user();

        return $user->hasRoles(['admin', 'chairperson', 'dean'])->exists();
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

        // Merge arrays
        $rules = array_merge($rules, [
            "website_url" => ['nullable', 'string', 'min:5', 'max:255'],
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ]);



        /**
         * METHOD: PUT
         * ALLOWED: Admin, Dean, Chairperson
         */
        if ($this->isMethod('put')) {

            //* Remove these attributes if the request is PUT
            unset($rules['id']);
            unset($rules['password']);


            $rules['first_name'] =  ['nullable', 'string'];
        }

        // Return rules
        return $rules;
    }
}
