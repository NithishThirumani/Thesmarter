<?php

namespace App\Modules\AdminFeature\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeatureStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_name' => 'required|string|max:255',
            'feature_type' => 'nullable|string|max:255',
            'feature_description' => 'nullable|string|max:16000',
            'feature_status' => 'required',
        ];
    }
}

