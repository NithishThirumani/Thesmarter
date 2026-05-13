<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = 'order_detail';
    protected $primaryKey = 'oid';
    protected $fillable = [
        'order_id',
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
        'order_date',
        'order_time',
        'order_type',
        'order_status',
        'view_flag',
        'discount_tax_inclusive'
    ];
    const CREATED_AT = 'create_dtm';
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
    public function customerAdditionalDetail()
    {
        return $this->hasOne(UserAdditionalDetail::class,'user_id','customer_id');
    }
    public function executive()
    {
        return $this->belongsTo(UserDetail::class, 'executive_id', 'user_id');
    }
    public function items()
    {
        return $this->hasMany(OrderItemDetail::class, 'order_id', 'order_id');
    }
    public function additional()
    {
        return $this->hasMany(OrderAdditionalDetail::class, 'order_id', 'order_id');
    }
    public function payment()
    {
        return $this->hasMany(OrderPayments::class, 'order_id', 'order_id');
    }
    public function miscellaneous()
    {
        return $this->hasMany(OrderMiscellaneousDetail::class, 'order_id', 'order_id');
    }
    public function charges()
    {
        return $this->hasMany(OrderCharges::class, 'order_id', 'order_id');
    }
    public function discount()
    {
        return $this->hasMany(OrderDiscounts::class, 'order_id', 'order_id');
    }
}
