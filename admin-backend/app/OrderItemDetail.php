<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItemDetail extends Model
{
    protected $table = 'order_item_detail';
    protected $primaryKey = 'order_item_id';
    protected $fillable = [
        'order_id',
        'product_id',
        'mpp_id',
        'discount_id',
        'product_quantity',
        'net_amount',
        'discount_amount',
        'tax_amount',
        'charge_amount',
        'total_amount',
        'custom_price_flag',
        'unit_price',
        'base_price'
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function product()
    {
        return $this->belongsTo(MerchantCatalogueProducts::class, 'product_id', 'product_id');
    }
    public function unitPrice()
    {
        return $this->belongsTo(MerchantProductPrices::class, 'mpp_id', 'mpp_id');
    }
    public function tax()
    {
        return $this->hasMany(OrderProductTaxes::class, 'order_id', 'order_id');
    }
    public function charge()
    {
        return $this->hasMany(OrderProductCharges::class, 'order_id', 'order_id');
    }
    public function discount()
    {
        return $this->hasMany(OrderProductDiscounts::class, 'order_id', 'order_id');
    }
}
