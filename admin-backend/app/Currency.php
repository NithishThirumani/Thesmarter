<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'currency';
    protected $primary_key = 'currency_id';
    protected $fillable = [
        'currency_name',
        'currency_code',
        'country_code',
        'currency_id',
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
