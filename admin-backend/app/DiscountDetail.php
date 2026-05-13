<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscountDetail extends Model
{
    protected $table = 'discount_detail';
    protected $primaryKey = 'discount_detail_id';
    protected $fillable = [
        'discount_id',
        'discount_name',
        'discount_start_date',
        'discount_end_date',
        'discount_value',
        'qualifying_value'

    ];
    public $timestamps = false;
    public function order()
    {
        return $this->hasMany(OrderProductDiscounts::class,'discount_detail_id','discount_detail_id');
    }
}
