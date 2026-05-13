<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppModules extends Model
{

    protected $table = 'app_modules';
    protected $primaryKey = 'module_id';
    protected $fillable  = [
        'module_name',
        'module_type',
        'module_description',
        'created_dtm'
    ];
    public $timestamps = false;
}
