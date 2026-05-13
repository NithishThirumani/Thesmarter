<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyPhoneAndPinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * For simplicity, always allow.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules based on the request type.
     */
    public function rules(): array
    {
        $routeName = $this->route()->getName(); // Fetch the route name

        if ($routeName === 'verify-phone') {
            return [
                'phone' => [
                    'required',
                    'string',
                    // 'regex:/^\+\d{10,15}$/', // Validates international phone numbers
                ],
            ];
        }

        if ($routeName === 'verify-pin') {
            return [
                'phone' => [
                    'required',
                    'string',
                    // 'regex:/^\+\d{10,15}$/',
                ],
                'pin' => [
                    'required',
                    'string',
                    'digits:4', // Ensures the PIN is exactly 4 digits
                ],
            ];
        }

        return [];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'The mobile number is required.',
            'phone.regex' => 'The mobile number must be a valid international number.',
            'pin.required' => 'The PIN is required.',
            'pin.digits' => 'The PIN must be exactly 4 digits.',
        ];
    }

    /**
     * Handle validation failure by throwing an appropriate response.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422));
    }
}

