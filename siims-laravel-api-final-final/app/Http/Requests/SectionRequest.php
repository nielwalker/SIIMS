<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SectionRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // Get authenticated user
        $authUser = $this->user();

        // Authorize
        return $authUser->hasRoles(['admin', 'coordinator', 'chairperson'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // Get current user
        $authUser = $this->user();

        // Get requested by
        $requestedBy = $this->input('requestedBy');

        // General rules
        $generalRules = [
            'name' => ['required', 'min:5', 'max:100', 'unique:sections,name'],
            'limit' => ['required', 'integer', 'min:1', 'max:60'],
            'class_list' => ['nullable', 'file', 'mimes:csv']
        ];

        // Check role
        if ($authUser->hasRole('admin') || $authUser->hasRole('chairperson')) {

            $generalRules['coordinator_id'] = ['required', 'exists:coordinators,id'];
        }


        if ($authUser->hasRole('admin') && $requestedBy === 'admin') {

            $generalRules['program_id'] =  ['required', 'exists:programs,id'];
        }

        // Return
        return $generalRules;
    }
}
