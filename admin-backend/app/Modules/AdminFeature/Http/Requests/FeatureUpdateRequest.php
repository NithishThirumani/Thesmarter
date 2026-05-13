<?php

namespace App\Modules\AdminFeature\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeatureUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_name' => 'sometimes|required|string|max:255',
            'feature_type' => 'sometimes|nullable|string|max:255',
            'feature_description' => 'sometimes|nullable|string|max:16000',
            'feature_status' => 'sometimes|required',
        ];
    }
}

