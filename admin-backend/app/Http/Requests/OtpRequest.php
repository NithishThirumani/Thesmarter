<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OtpRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function isSendRequest(): bool
    {
        return $this->routeIs('auth.send-otp') || $this->routeIs('auth.resend-otp');
    }
    public function isVerifyRequest(): bool
    {
        return $this->routeIs('auth.verify-otp');
    }
    public  function isResendRequest(): bool
    {
        return $this->routeIs('auth.resend-otp');;
    }

    public function rules(): array
    {
        if ($this->isSendRequest() || $this->isResendRequest()) {
            return [
                'email' => 'required|email|max:255',
            ];
        } elseif ($this->isVerifyRequest()) {
            return [
                'email' => 'required|email|max:255',
                'otp' => 'required|string|max:10',
            ];
        }
        return [];
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
