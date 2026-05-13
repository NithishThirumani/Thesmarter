<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaItemDetail extends Model
{
    protected $table = 'proforma_item_detail';
    protected $primaryKey = 'proforma_item_id';
    protected $fillable = [
        'proforma_id',
        'product_id',
        'mpp_id',
        'tax_id',
        'discount_id',
        'unit_price',
        'base_amount',
        'product_quantity',
        'net_amount',
        'discount_amount',
        'tax_amount',
        'charge_amount',
        'total_amount',
        'is_dynamically_priced'
    ];
    const CREATED_AT = 'created_dtm';
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
        return $this->hasMany(ProformaProductTaxes::class, 'proforma_id', 'proforma_id');   
    }
    public function discount()
    {
        return $this->hasMany(ProformaProductDiscounts::class, 'proforma_id', 'proforma_id');
    }
}
