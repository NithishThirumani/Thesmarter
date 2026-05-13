<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantProductPrices extends Model
{
    protected $table ="merchant_product_prices";
    protected $fillable = [
        'product_id',
        'product_cost_price',
        'product_sell_price',
        'start_dtm',
        'end_dtm',
        'price_status',

    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
