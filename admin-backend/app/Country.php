<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';
    protected $primaryKey = 'country_id';
    protected $fillable = [
        'country_code',
        'country_name',
        'mobile_format_id',
        'currency_id',
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function currency()
    {
        return $this->hasOne(Currency::class,'currency_id','currency_id');
    }
}
