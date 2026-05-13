<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantProductDiscount extends Model
{
    protected $table ="merchant_product_discount";
    protected $fillable = [
        'product_id',
        'discount_id',
        'status'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
