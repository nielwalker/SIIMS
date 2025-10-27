<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProgramRequest extends FormRequest
{
    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * ProgramRequest constructor.
     */
    public function __construct()
    {
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow Admin, Dean, and Chairperson
        return $this->user->hasRoles(['admin', 'dean', 'chairperson'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // General validation rules
        $validation_rules = [
            "name" => ['required', 'min:5', 'max:100', 'string']
        ];

        /**
         * - POST METHOD
         * - FOR ADMIN AND DEAN
         */
        if ($this->isMethod('post') && $this->user->hasRoles(['admin', 'dean', 'chairperson'])) {
            $validation_rules['name'] = ['required', 'min:5', 'max:100', 'string', 'unique:programs,name'];

            // ! For Admin Only
            if ($this->user->hasRole('admin')) {
                $validation_rules['college_id'] = ['required', 'integer', 'exists:colleges,id'];
            }
        }

        /**
         * - PUT METHOD
         * - FOR ADMIN
         */
        if ($this->isMethod('put') && $this->user->hasRole('admin')) {
            $validation_rules['name'] = ['required', 'min:5', 'max:100', 'string'];
            $validation_rules['chairperson_id'] = ['nullable', 'integer', 'exists:users,id'];
        }

        // Return validation rules
        return $validation_rules;
    }
}
