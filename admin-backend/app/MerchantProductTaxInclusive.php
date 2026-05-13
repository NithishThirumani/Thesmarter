<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantProductTaxInclusive extends Model
{
    protected $table ="merchant_product_tax_inclusive";
    protected $fillable = [
        'product_id',
        'inclusive_flag',
        'start_date_time',
        'end_date_time',
        'current_status'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
