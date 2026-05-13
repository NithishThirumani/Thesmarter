<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListProformaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_id' => 'required|integer',
            'limit' => 'sometimes|integer|min:1',
            'offset' => 'sometimes|integer|min:0',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
            'proforma_no' => 'sometimes|string',
            'proforma_status' => 'sometimes|array',
            'proforma_status.*' => 'string',
            'branch_id' => 'sometimes|array',
            'customer_id' => 'sometimes|integer',
            'customer_phone' => 'sometimes|integer',
            'customer_name' => 'sometimes|string',
        ];
    }
    public function messages()
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.integer' => 'The company ID must be a valid integer.',
            'limit.integer' => 'The limit must be a valid integer',
            'limit.min' => 'Limit should be at leat one (1)',
            'offset.integer' => 'The offset must be a valid integer',
            'from_data.data' => 'The from Date must be  a valid date',
            'to_data.date' => 'The to Date must be a valid date',
            'proforma_status.array' => 'The proforma status field must be an array.',
            'branch_id.array' => 'The branch_id field must be an array.',
            'customer_phone.integer' => 'The customer Phone  must be a valid number',
            'customer_name.string' => 'The customer Name  must be a valid string',
            'proforma_no.string' => 'The Proforma Number  must be a valid string',
            'customer_id.integer' => 'The customer ID must be a valid integer'


        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
