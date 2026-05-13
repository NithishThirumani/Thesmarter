<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyFeatures extends Model
{
    protected $table = 'company_features';
    protected $primaryKey = 'cf_id';
    protected $fillable = [
        'feature_id',
        'company_id',
        'company_feature_status'
    ];
    public $timestamps = false;

    public function feature()
    {
        return $this->belongsTo(AppFeatures::class, 'feature_id', 'feature_id');
    }
}
