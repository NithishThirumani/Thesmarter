<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppFeatures extends Model
{

    protected $table = 'app_features';
    protected $primaryKey = 'feature_id';
    protected $fillable  = [
        'feature_name',
        'feature_type',
        'feature_description',
        'feature_status'
    ];
    public $timestamps = false;
}
