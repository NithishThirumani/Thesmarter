<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderDuesDetail extends Model
{
    protected $table = 'order_dues_detail';
    protected $primaryKey = 'odd_id';
    protected $fillable = [
        'order_id',
        'due_id',
        'order_due_amount',
        'order_due_cleared',
        'executive_id',
        'order_due_status'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
