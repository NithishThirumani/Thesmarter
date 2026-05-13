<?php

namespace App\Modules\AdminCompany\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExecutiveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('permissions')) {
            $p = $this->input('permissions');
            if (is_string($p)) {
                $decoded = json_decode($p, true);
                $this->merge(['permissions' => is_array($decoded) ? $decoded : []]);
            }
        }

        if ($this->has('branch_ids')) {
            $raw = $this->input('branch_ids');
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $this->merge(['branch_ids' => is_array($decoded) ? $decoded : []]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:191',
            'last_name' => 'nullable|string|max:191',
            'mobile' => 'required|string|max:32',
            'email' => 'required|email|max:191',
            'gender' => 'nullable|string|in:M,F,O',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string|in:S,M,D,W,NA',
            'confirm_convert_owner' => 'sometimes|boolean',
            'confirm_promote_customer' => 'sometimes|boolean',
            'branch_ids' => 'required|array|size:1',
            'branch_ids.*' => 'integer',
            'permissions' => 'sometimes|array',
            'permissions.*.module_id' => 'required_with:permissions|integer',
            'permissions.*.Access_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Read_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Create_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Update_priv' => 'sometimes|string|in:Y,N',
            'permissions.*.Delete_priv' => 'sometimes|string|in:Y,N',
            'avatar' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048',
        ];
    }
}
