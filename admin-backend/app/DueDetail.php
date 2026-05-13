<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DueDetail extends Model
{
    protected $table = 'dues_detail';
    protected $primaryKey = 'due_id';
    protected $fillable = [
        'due_amount',
        'due_status',
        'customer_id',
        'company_id'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
