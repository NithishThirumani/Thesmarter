<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyBusinessHour extends Model
{
    protected $table = 'company_business_hours';

    protected $fillable = [
        'company_id',
        'day_of_week',
        'is_open',
        'opening_time',
        'closing_time',
        'slot_index',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'slot_index' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id', 'company_id');
    }
}
