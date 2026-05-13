<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderPayments extends Model
{
    protected $table = 'order_payments';
    protected $primaryKey = 'op_id';
    protected $fillable = [
        'order_id',
        'product_id',
        'payment_mode_id',
        'payment_reference',
        'amount_paid',
        'amount_balance',
        'amount_returned',
        'details'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMethods::class,'payment_mode_id','payment_id');
    }
}
