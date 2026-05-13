<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderProductTaxes extends Model
{
    protected $table = 'order_product_taxes';
    protected $primaryKey = "pt_id";
    protected $fillable = [
        'order_id',
        'product_id',
        'td_id',
        'value',
        'name',
        'amount'
    ];
    public $timestamps = false;

    public function details()
    {
        return $this->hasOne(TaxDetail::class, 'td_id', 'td_id');   
    }
}
