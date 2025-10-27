<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends UserRequest
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

        // Get authenticated user
        $authUser = $this->user();

        // Get the rules from User Request
        $rules = parent::rules();

        // If User is Admin Do this
        /* if($authUser->hasRole('admin')) {

            

        } */

        // Merge array of rules
        $rules = array_merge($rules, [
            "website_url" => ['nullable', 'string', 'min:5', 'max:255'],
            'name' => ['nullable', 'string', 'min:2', 'max:255'],
        ]);

        // ! FOR STUDENT
        if($authUser->hasRole('student')) {

            $rules = array_merge($rules, [
                "about_me" => ['required', 'string', 'min:10', 'max:1000'],
                "date_of_birth" => ['required', 'date'],
            ]);
        }

        // Return rules
        return $rules;

        /* if($authUser->hasRole('student')) {
            return [
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone_number' => 'nullable|string|max:20',
                'street' => 'nullable|string|max:255',
                'barangay' => 'nullable|string|max:255',
                'city_municipality' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
                'gender' => 'nullable|string|in:Male,Female,Other',
                'work_experiences' => 'nullable|array',
                'work_experiences.*.id' => 'nullable|exists:work_experiences,id',
                'work_experiences.*.job_position' => 'required|string|max:255',
                'work_experiences.*.company_name' => 'required|string|max:255',
                'work_experiences.*.start_date' => 'required|date',
                'work_experiences.*.end_date' => 'nullable|date|after_or_equal:work_experiences.*.start_date',
                'educations' => 'nullable|array',
                'educations.*.id' => 'nullable|exists:educations,id',
                'educations.*.school_name' => 'required|string|max:255',
                'educations.*.full_address' => 'required|string|max:255',
                'educations.*.start_date' => 'required|date',
                'educations.*.end_date' => 'nullable|date|after_or_equal:educations.*.start_date',
            ];
        } */
    }
}
