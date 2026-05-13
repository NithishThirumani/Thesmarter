<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'branch_id' => 'required|integer',
            'limit' => 'sometimes|integer|min:1',
            'offset' => 'sometimes|integer|min:0',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
            'proforma_status' => 'sometimes|array',
            'proforma_status.*' => 'string',
            'branch_id' => 'sometimes|array',
            'branch_id.*' => 'integer',
            'customer_number' => 'integer',
            'customer_name.*' => 'string',
        ];
    }
    public function messages()
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.integer' => 'The company ID must be a valid integer.',
            'branch_id.integer' => 'The branch ID must be a valid integer.',
            'limit.integer' => 'The limit must be a valid integer',
            'limit.min' => 'Limit should be at leat one (1)',
            'offset.integer' => 'The offset must be a valid integer',
            'from_data.data' => 'The from Date must be  a valid date',
            'to_data.date' => 'The to Date must be a valid date',
            'branch_id.array' => 'The items field must be an array.',
            'customer_phone.integer' => 'The customer Phone  must be a valid number',
            'customer_name.string' => 'The customer Name  must be a valid string',


        ];
    }
}
