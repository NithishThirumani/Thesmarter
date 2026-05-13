<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxDetail extends Model
{
    protected $table = 'tax_details';
    protected $fillable = [
        'tc_id',
        'tax_value',
        'tax_start_date',
        'tax_end_date'

    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function component()
    {
        return $this->belongsTo(TaxComponents::class,'tc_id','tc_id');
    }
    public function order()
    {
        return $this->hasMany(OrderProductTaxes::class,'td_id','td_id');
    }
}
