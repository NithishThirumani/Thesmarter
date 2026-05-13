<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProformaDetailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'proformaNo' => [
                'required',
                'string',
                'regex:/^PF\d{4}-\d{3}$/', // Example regex for PF2025-001 format
                // Rule::exists('proforma_details', 'proforma_no'), // Check if it exists in the datab
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proformaNo.required' => 'The proforma number is required.',
            'proformaNo.regex' => 'The proforma number must be in the format PF2025-001.',
        ];
    }

    /**
     * Get the route parameters for validation.
     */
    protected function prepareForValidation()
    {
       
        $this->merge([
            'proformaNo' => $this->route('proformaNo'),
        ]);
    }
}
