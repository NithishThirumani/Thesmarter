<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendancePunchinRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Use authorization logic if needed
    }

    public function rules()
    {
        return [
            'company_id' => 'required|integer',
            'branch_id'=>'required|integer',
            'user_id' => 'required|integer',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'in_date_time'=>'date|date_format:Y-m-d H:i:s'
        ];
    }
}
