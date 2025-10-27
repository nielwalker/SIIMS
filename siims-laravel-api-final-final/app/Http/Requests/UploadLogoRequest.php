<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // Get authenticated user
        $user = $this->user();

        return $user && $user->hasRoles(['admin','company'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // Get authenticated user
        $authUser = $this->user();

        // Declare $rules
        $rules = [
            // Ensure a logo file is provided and is an image with specific mime types
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20000',  // Max file size 2MB
        ];

        // ! FOR ADMIN
        if($authUser->hasRole('admin')) {

            // * Append new rule 'company_id'
            $rules['company_id'] = ['required', 'exists:companies,id'];
        }

        // Return rules
        return $rules;
    }
}
