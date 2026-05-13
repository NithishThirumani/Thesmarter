<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $table = 'notification_template';
    protected $fillable = [
        'name',
        'event_id',
        'priority',
        'type_id',
        'isActive'
    ];

    public $timestamp = false;
}
