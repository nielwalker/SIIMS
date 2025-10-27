<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class DocumentTypeRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        // Get authenticated user
        $user = $this->user();

        // Check if User Roles has an Admin or OSA
        return $user->hasRoles(['admin', 'osa'])->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // General rules
        $generalRules = [
            "name" => ['required', 'string', 'min:3', 'max:100', 'unique:document_types,name'],
        ];

        // PUT METHOD
        if ($this->isMethod('put')) {

            // Get the document type ID from the URL parameters
            $documentTypeID = $this->route('document_type_id');

            $generalRules['name'] = ['required', 'string', 'min:3', 'max:100', "unique:document_types,name,{$documentTypeID},id"];
          
        }

        // Return
        return $generalRules;
    }
}
