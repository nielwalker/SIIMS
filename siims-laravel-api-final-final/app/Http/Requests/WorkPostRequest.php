<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkPostRequest extends FormRequest
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

        // FOR POSTS
        if($this->isMethod('post')) {
            return [
                "office_id" => "required|integer",
                "work_type_id" => "required|integer",
                "title" => "required|string|min:5|max:255",
                "responsibilities" => "string",
                "qualifications" => "string",
                "start_date" => "required",
                "end_date" => "required",
                "work_duration" => "nullable|integer",
            ];
        }

        return [
            //
        ];
    }
}
