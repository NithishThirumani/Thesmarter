<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaDetails extends Model
{
    protected $table = 'proforma_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'proforma_no',
        'company_id',
        'branch_id',
        'customer_id',
        'executive_id',
        'net_amount',
        'discount_amount',
        'tax_amount',
        'charge_amount',
        'total_amount',
        'discount_id',
        'proforma_date_time',
        'proforma_type',
        'charge_id',
        'proforma_status',
        'view_flag',
        'discount_tax_inclusive'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function company()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id', 'company_id');
    }
    public function branch()
    {
        return $this->belongsTo(BranchDetail::class, 'branch_id', 'branch_id');
    }
    public function customer()
    {
        return $this->belongsTo(UserDetail::class, 'customer_id', 'user_id');
    }
    public function customePhone()
    {
        return $this->belongsTo(UserLogin::class, 'customer_id', 'user_id');
    }
    public function executive()
    {
        return $this->belongsTo(UserDetail::class, 'executive_id', 'user_id');
    }
    public function items()
    {
        return $this->hasMany(ProformaItemDetail::class, 'proforma_id', 'id');
    }
 
    public function miscellaneous()
    {
        return $this->hasMany(ProformaMiscellaneousDetail::class, 'proforma_id', 'id');
    }
    public function customerAdditionalDetail()
    {
        return $this->hasOne(UserAdditionalDetail::class,'user_id','customer_id');
    }
}
