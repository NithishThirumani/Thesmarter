<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderCharges extends Model
{
    protected $table = 'order_charges';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id',
        'charge_id',
        'name',
        'value',
        'amount'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';


}