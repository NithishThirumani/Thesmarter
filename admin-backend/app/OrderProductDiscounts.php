<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderProductDiscounts extends Model
{
    protected $table = 'order_product_discounts';
    protected $primaryKey = 'pd_id';
    protected $fillable = [
        'order_id',
        'product_id',
        'discount_detail_id',
        'name',
        'value',
        'type',
        'level',
        'amount'
    ];
    public $timestamps = false;
}
