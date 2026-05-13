<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderShipping extends Model
{
    protected $table = 'order_shipping';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id',
        'address',
        'area',
        'city',
        'state',
        'country',
        'coordinates',
        'email'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';


}