<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateProformaRequest extends FormRequest
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
            'customer_id' => 'required|integer',
            'executive_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer', // Validates each product's ID
            'items.*.quantity' => 'required|numeric|min:1', // Ensures each product has a valid quantity
            'discount_id' => 'nullable|integer',
            'charge_id' => 'nullable|integer',
        ];
    }
    public function messages()
    {
        return [
            'company_id.required' => 'The company ID is required.',
            'company_id.integer' => 'The company ID must be a valid integer.',

            'branch_id.required' => 'The branch ID is required.',
            'branch_id.integer' => 'The branch ID must be a valid integer.',

            'customer_id.required' => 'The customer ID is required.',
            'customer_id.integer' => 'The customer ID must be a valid integer.',

            'executive_id.required' => 'The executive ID is required.',
            'executive_id.integer' => 'The executive ID must be a valid integer.',

            'items.required' => 'At least one item is required in the proforma.',
            'items.array' => 'The items field must be an array.',
            'items.min' => 'You must add at least one item.',

            'items.*.product_id.required' => 'Each item must have a product ID.',
            'items.*.product_id.integer' => 'Each product ID must be a valid integer.',

            'items.*.quantity.required' => 'Each item must have a quantity.',
            'items.*.quantity.numeric' => 'Each quantity must be a valid number.',
            'items.*.quantity.min' => 'Each quantity must be at least 1.',

            'discount_id.integer' => 'The discount ID must be a valid integer.',
            'charge_id.integer' => 'The charge ID must be a valid integer.',
        ];
    }
}
