<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // Get User
        $user = $this->user();

        // Authorize this roles only
        return Auth::check() && $user->hasRoles(['admin', 'dean', 'chairperson', 'student', 'coordinator'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // Get User
        $user = $this->user();

        // General Attributes
        $generalAttributes = [
            "first_name" => ['required', 'string', 'min:3'],
            "email" => ['required', 'email', 'min:3', 'unique:users,email'],
        ];

        /**
         * METHOD: POST
         * Roles Allowed: Admin
         */
        if ($this->isMethod('post') || $user->hasRole('admin')) {

            $generalAttributes = array_merge($generalAttributes, [
                "id" => ['required', 'unique:users,id'],
                "middle_name" => ['nullable', 'string'],
                "last_name" => ['nullable', 'string'],
                "password" => ['required', 'min:8', ' string'],
                'gender' => ['nullable', 'string', 'in:male,female,other'],
                'phone_number' => ['nullable', 'string'],
                "street" => ['nullable', 'string'],
                "barangay" => ['nullable', 'string'],
                "city_municipality" => ['nullable', 'string'],
                "province" => ['nullable', 'string'],
                "postal_code" => ['nullable', 'string'],
            ]);
        }

        /**
         * METHOD: PUT
         */
        if ($this->isMethod('put') || $this->isMethod('patch')) {

            $generalAttributes = array_merge($generalAttributes, [
                "middle_name" => ['nullable', 'string'],
                "email" => ['required', 'email', 'min:3'],
                "last_name" => ['nullable', 'string'],
                'gender' => ['nullable', 'string'],
                'phone_number' => ['nullable', 'string'],
                "street" => ['nullable', 'string'],
                "barangay" => ['nullable', 'string'],
                "city_municipality" => ['nullable', 'string'],
                "province" => ['nullable', 'string'],
                "postal_code" => ['nullable', 'string'],
            ]);
        }

        // For Delete
        if ($this->isMethod('delete')) {
            return [
                'ids' => ['required'],
                'ids.*' => ['integer'],
            ];
        }

        // Return General Attributes
        return $generalAttributes;
    }
}
