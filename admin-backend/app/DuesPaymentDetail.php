<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DuesPaymentDetail extends Model
{
    protected $table = 'dues_payment_detail';
    protected $primaryKey = 'pay_id';
    protected $fillable = [
        'due_id',
        'executive_id',
        'payment_amount',
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
