<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaProductDiscounts extends Model
{
    protected $table = 'proforma_product_discounts';
    protected $primaryKey = 'pd_id';
    protected $fillable = [
        'proforma_id',
        'product_id',
        'discount_detail_id',
        'value',
        'name',
        'type',
        'amount'
    ];
    public $timestamps = false;
   
}
