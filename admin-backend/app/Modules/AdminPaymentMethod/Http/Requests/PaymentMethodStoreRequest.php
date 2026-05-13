<?php

namespace App\Modules\AdminPaymentMethod\Http\Requests;

use App\Support\PaymentMethodAllowedTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('payment_type')) {
            return;
        }

        $this->merge([
            'payment_type' => PaymentMethodAllowedTypes::migrateFromLegacy($this->input('payment_type')),
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_name' => 'required|string|max:255',
            'payment_description' => 'nullable|string|max:2000',
            'active_status' => 'required|integer|in:0,1',
            'payment_type' => ['required', 'string', 'max:64', Rule::in(PaymentMethodAllowedTypes::values())],
        ];
    }
}
