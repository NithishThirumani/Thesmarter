<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationChannels extends Model
{
    protected $table = 'notification_channels';
    protected $fillable = [
        'name',
        'isActive'
    ];
    public $timestamps = false;
}
