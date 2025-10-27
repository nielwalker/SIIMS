<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyWorkPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        
        $general_rules = [
            "work_type_id" => ['required', 'integer'],
            "title" => ['required', 'string', 'min:5', 'max:255'],
            "responsibilities" => ['string',],
            "max_applicants" => ['required', 'integer'],
            "qualifications" => ['nullable', 'string'],
            "start_date" => ['required', 'date'],
            "end_date" => ['required', 'required', 'after:start_date'],
            "work_duration" => ['nullable', 'string'],
        ];

        if($this->isMethod('post')) {
            $general_rules['office_id'] = ['required', 'integer'];
        }

        return $general_rules;
    }
}
