<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantCatalogue extends Model
{
    protected $table = 'merchant_catalogue';
    protected $primaryKey = 'catalogue_id';
    protected $fillable = [
        'company_id',
        'lob_id',
        'catalogue_status'
    ];
   public $timestamps = false;

    public function company()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id', 'company_id');
    }

    public function lineOfBusiness()
    {
        return $this->belongsTo(LineOfBusiness::class, 'lob_id', 'lob_id');
    }
}
