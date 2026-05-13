<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscountMaster extends Model
{
    protected $table = 'discount_master';
    protected $primaryKey = 'discount_detail_id';
    protected $fillable = [
        'discount_id',
        'company_id',
        'discount_level',
        'discount_type',
        'dv_id',
        'discount_status'

    ];
    public $timestamps = false;
    public function details()
    {
        return $this->hasMany(DiscountDetail::class, 'discount_id', 'discount_id');
    }
    // public function variation()
    // {
    //     return $this->belongsTo(DiscountVariation::class, 'dv_id', 'dv_id');
    // }
}
