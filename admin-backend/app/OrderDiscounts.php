<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderDiscounts extends Model
{
    protected $table = 'order_discounts';
    protected $primaryKey = 'od_id';
    protected $fillable = [
        'order_id',
        'dd_id',
        'name',
        'type',
        'value',
        'amount'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';


}