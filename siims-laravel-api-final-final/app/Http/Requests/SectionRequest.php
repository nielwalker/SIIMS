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

        // Get requested by - check both input and query
        $requestedBy = $this->input('requestedBy') ?? $this->query('requestedBy');

        // Get section ID from route for update operations
        // Try multiple ways to get the route parameter
        $sectionId = $this->route('section_id') 
            ?? ($this->route() ? $this->route()->parameter('section_id') : null)
            ?? null;
        
        // Convert to string for validation rule if it exists
        if ($sectionId !== null) {
            $sectionId = (string) $sectionId;
        }
        
        // Determine if this is an update request
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        
        // Build unique rule for name - ignore current section when updating
        $nameUniqueRule = 'unique:sections,name';
        if ($isUpdate && $sectionId) {
            $nameUniqueRule = "unique:sections,name,{$sectionId},id";
        }

        // General rules - different for create vs update
        if ($isUpdate) {
            // For UPDATE: Fields are required if provided (always provided in our case)
            $generalRules = [
                'name' => ['required', 'min:2', 'max:100', $nameUniqueRule],
                'limit' => ['nullable', 'integer', 'min:0', 'max:60'],
                'class_list' => ['nullable', 'file', 'mimes:csv']
            ];

            // Check role - for updates, validate if user has permission
            if ($authUser->hasRole('admin') || $authUser->hasRole('chairperson')) {
                $generalRules['coordinator_id'] = ['required', 'exists:coordinators,id'];
            }

            if (($authUser->hasRole('admin') && $requestedBy === 'admin') || $authUser->hasRole('chairperson')) {
                $generalRules['program_id'] = ['required', 'exists:programs,id'];
            }
        } else {
            // For CREATE: All fields are required based on role
            $generalRules = [
                'name' => ['required', 'min:2', 'max:100', $nameUniqueRule],
                'limit' => ['nullable', 'integer', 'min:0', 'max:60'],
                'class_list' => ['nullable', 'file', 'mimes:csv']
            ];

            // Check role
            if ($authUser->hasRole('admin') || $authUser->hasRole('chairperson')) {
                $generalRules['coordinator_id'] = ['required', 'exists:coordinators,id'];
            }

            if (($authUser->hasRole('admin') && $requestedBy === 'admin') || $authUser->hasRole('chairperson')) {
                $generalRules['program_id'] = ['required', 'exists:programs,id'];
            }
        }

        // Return
        return $generalRules;
    }
}
