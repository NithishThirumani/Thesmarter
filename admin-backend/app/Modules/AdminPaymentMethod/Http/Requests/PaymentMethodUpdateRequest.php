<?php

namespace App\Modules\AdminPaymentMethod\Http\Requests;

use App\Support\PaymentMethodAllowedTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodUpdateRequest extends FormRequest
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
            'payment_name' => 'sometimes|required|string|max:255',
            'payment_description' => 'sometimes|nullable|string|max:2000',
            'active_status' => 'sometimes|integer|in:0,1',
            'payment_type' => ['sometimes', 'string', 'max:64', Rule::in(PaymentMethodAllowedTypes::values())],
        ];
    }
}
