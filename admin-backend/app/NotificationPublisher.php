<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationPublisher extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'notification_publisher';
    protected $fillable = [
        'notification_id',
        'company_id',
        'branch_id',
        'user_id'
    ];
    public $timestamps  = false;
}
