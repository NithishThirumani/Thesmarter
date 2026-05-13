<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        // Define validation rules for user registration
        return [
            // Basic user info
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255', // adjust table/column
            // 'type' => 'required|in:phone,email,employee_id',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'dob' => 'nullable|date',
            'marital_status' => 'nullable|in:S,M,D,W,NA',
            'gender' => 'nullable|in:M,F,O,NA',

            // User type and company
            'user_type' => 'nullable|in:employee,customer',
            'company_id' => 'nullable',

            // Additional details (dynamic fields)
            'additional_details' => 'nullable|array',
            'additional_details.*' => 'array',
            'additional_details.*.value' => 'required_with:additional_details.*|string',
            'additional_details.*.is_printable' => 'required_with:additional_details.*|boolean',

            // Address rules (not mandatory but with conditional requirements)
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'required_with:addresses|in:home,work,others',
            'addresses.*.is_primary' => 'required_with:addresses|boolean',
            'addresses.*.address' => 'required_with:addresses.*.state|string',
            'addresses.*.state' => 'required_with:addresses.*.address|string',
            'addresses.*.country' => 'required_with:addresses.*.address|string',
            'addresses.*.pin_code' => 'required_with:addresses.*.address|string',
        ];
    }


    // public function messages()
    // {
    //     return [
    //         'identifier.required' => 'The phone or email is required.',
    //         'company_id.required' => 'The company id is  required',
    //         'type.required' => 'The type is required.',
    //     ];
    // }
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $firstMessage = collect($errors)->flatten()->first();
        throw new HttpResponseException(
            response()->json([
                'error_flag' => true,
                'errors' => $firstMessage
            ], 422)
        );
    }
}
