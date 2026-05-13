<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantProductTaxes extends Model
{
    protected $table ="merchant_product_taxes";
    protected $fillable = [
        'product_id',
        'tax_id',
        'status',
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
