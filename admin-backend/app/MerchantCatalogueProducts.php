<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantCatalogueProducts extends Model
{
    protected $table = 'merchant_catalogue_products';
    protected $primaryKey = 'product_id';
    protected $fillable = [
        'catalogue_id',
        'product_type',
        'product_logo',
        'product_name',
        'product_brand',
        'product_code',
        'product_hsn_code',
        'product_barcode',
        'product_qr_code',
        'quantity_based_price_flag',
        'product_service_charge_flag',
        'product_discount_flag',
        'product_count_stock',
        'measuring_unit_id',
        'product_status'

    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';




    public function price()
    {
        return $this->hasOne(MerchantProductPrices::class, 'product_id')
            ->where('price_status', 'A');
    }
    public function tax()
    {
        return $this->hasOne(MerchantProductTaxes::class, 'product_id')
            ->where('status', 'A');
    }
    public function discount()
    {
        return $this->hasOne(MerchantProductDiscount::class, 'product_id')
            ->where('status', 'A');
    }
    public function taxInclusive()
    {
        return $this->hasOne(MerchantProductTaxInclusive::class, 'product_id')
                    ->where('current_status', 'A');
    }
}
