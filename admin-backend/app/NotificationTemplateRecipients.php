<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplateRecipients extends Model
{
    protected $table = 'notification_template_recipients';
    protected $fillable = [
        'template_id',
        'recipient',
        'channel',
        'message_format',
        'active'
    ];

    public $timestamp = false;
}
