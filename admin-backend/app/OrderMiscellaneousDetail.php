<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderMiscellaneousDetail extends Model
{
    protected $table = 'order_miscellaneous_detail';
    protected $primaryKey = 'omd_id';
    protected $fillable = [
        'order_id',
        'misc_id',
        'charge_amount'
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';
}
