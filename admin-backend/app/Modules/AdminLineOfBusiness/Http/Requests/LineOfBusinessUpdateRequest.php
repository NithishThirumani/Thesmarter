<?php

namespace App\Modules\AdminLineOfBusiness\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LineOfBusinessUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lob_name' => 'sometimes|required|string|max:255',
            'lob_description' => 'sometimes|nullable|string|max:1000',
            'lob_status' => 'sometimes|required|string|max:10',
        ];
    }
}

