<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'company_id',
        'enforce_2fa',
        'geo_location_tracking',
        'geo_fencing_enabled',
        'geo_fencing_radius',
        'appointment_time_slice_enabled',
        'appointment_time_slice_minutes',
        'auto_approve_appointments',
        'marketing_message',
        'public_company_page',
    ];

    protected $casts = [
        'enforce_2fa' => 'boolean',
        'geo_location_tracking' => 'boolean',
        'geo_fencing_enabled' => 'boolean',
        'appointment_time_slice_enabled' => 'boolean',
        'auto_approve_appointments' => 'boolean',
        'public_company_page' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id', 'company_id');
    }
}
