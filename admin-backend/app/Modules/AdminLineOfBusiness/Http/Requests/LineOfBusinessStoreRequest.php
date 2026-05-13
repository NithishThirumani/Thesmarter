<?php

namespace App\Modules\AdminLineOfBusiness\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LineOfBusinessStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lob_name' => 'required|string|max:255',
            'lob_description' => 'nullable|string|max:1000',
            'lob_status' => 'required|string|max:10',
        ];
    }
}

