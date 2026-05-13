<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FeatureModules extends Model
{
    protected $table = 'feature_modules';

    public $timestamps = false;

    protected $fillable = ['feature_id', 'module_id', 'status'];
}
