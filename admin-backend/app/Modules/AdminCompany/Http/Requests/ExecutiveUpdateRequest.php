<?php

namespace App\Modules\AdminCompany\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExecutiveUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('permissions')) {
            return;
        }

        $p = $this->input('permissions');
        if (is_string($p)) {
            $decoded = json_decode($p, true);
            $this->merge(['permissions' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|string|max:191',
            'last_name' => 'nullable|string|max:191',
            'email' => 'sometimes|email|max:191',
            'gender' => 'sometimes|nullable|string|in:M,F,O',
            'date_of_birth' => 'sometimes|nullable|date',
            'marital_status' => 'sometimes|nullable|string|in:S,M,D,W,NA',
            'permissions' => 'sometimes|array',
            'permissions.*.module_id' => 'required_with:permissions|integer',
            'permissions.*.Access_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Read_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Create_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Update_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Delete_priv' => 'sometimes|string|in:Y,N',
            'is_active' => 'sometimes|boolean',
            'remove_avatar' => 'sometimes|boolean',
            'avatar' => 'sometimes|nullable|file|mimes:jpeg,jpg,png,webp|max:2048',
        ];
    }
}
