<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderProductCharges extends Model
{
    protected $table = 'order_product_charges';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id',
        'product_id',
        'charge_id',
        'name',
        'value',
        'type',
        'level',
        'amount'
    ];
    public $timestamps = false;
}
