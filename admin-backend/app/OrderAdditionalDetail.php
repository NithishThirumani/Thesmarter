<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderAdditionalDetail extends Model
{
    protected $table = 'order_additional_detail';
    protected $primaryKey = 'oad_id';
    protected $fillable = [
        'order_id',
        'manager_id',
        'manager_name',
        'distributor_id',
        'distributor_name',
        'route_no',
        'delivery_date',
        'delivery_status',
        'order_created_dtm',
        'bill_no',
        'pan_number',
        'return_date',
        'payment_type',
        'deposit_name'
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';
}
