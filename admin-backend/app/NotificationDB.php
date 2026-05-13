<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationDB extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'notifications';
    protected $fillable = [
        'event_id',
        'message',
        'redirect',
        'sent_on',
        'payload'
    ];

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id','id');
    }
    public function publisher()
    {
        return $this->hasOne(NotificationPublisher::class, 'notification_id','id');
    }
   
}
