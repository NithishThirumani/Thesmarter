<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaProductTaxes extends Model
{
    protected $table = 'proforma_product_taxes';
    protected $primaryKey = "pt_id";
    protected $fillable = [
        'proforma_id',
        'product_id',
        'td_id',
        'value',
        'amount',
        'name'
    ];
    public $timestamps = false;

    public function details()
    {
        return $this->hasOne(TaxDetail::class, 'td_id', 'td_id');
    }
    public function proformaItem()
    {
        return $this->belongsTo(ProformaItemDetail::class, 'proforma_id', 'proforma_id');
    }
}
