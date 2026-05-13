<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppEvents extends Model
{
    protected $table = 'app_events';
    protected $fillable = [
        'name',
        'isActive'
    ];
    public $timestamps = false;
}
