<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderEntity extends Model
{
    protected $table = 'order_entity';
    protected $primaryKey = 'oe_id';
    protected $fillable = [
        'order_item_id',
        'executive_id',
        'order_item_quantity',
        'picked_dtm',
        'delivery_dtm',
        'order_item_status',
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';
}
