<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFlexibleTimings extends Model
{
    
    protected $table = 'user_flexible_timings';
    protected $fillable = [
        'user_id',
        'flexible_timing_status'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    
}
