<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethods extends Model
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'payment_name',
        'payment_description',
        'active_status',
        'payment_type'
    ];

    protected $casts = [
        'active_status' => 'integer',
    ];

    /**
     * Legacy schema: rows use create_dtm; many installs have no separate "updated" column.
     * Setting UPDATED_AT to null tells Laravel not to INSERT/UPDATE a second timestamp column.
     */
    const CREATED_AT = 'create_dtm';

    const UPDATED_AT = null;
}
