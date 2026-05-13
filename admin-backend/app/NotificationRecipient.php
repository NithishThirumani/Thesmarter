<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'notification_recipient';
    protected $fillable = [
        'user_id',
        'notification_id',
        'app_type',
        'company_id'
    ];
    public $timestamps = false;

    
    
}
