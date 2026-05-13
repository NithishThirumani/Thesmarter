<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MetricUnits extends Model
{
    protected $table = "metric_units";
    protected $primaryKey = "unit_id";
    protected $fillable = [
        'unit_name',
        'unit_abrevation',
        'unit_status'
    ];
    const CREATED_AT = 'created_dtm';
}
