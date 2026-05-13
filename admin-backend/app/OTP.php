<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class OTP extends Model
{
    //
    use Notifiable;
    protected $table = 'otp';
    protected $fillable = [
        'user_mobile',
        'secret_code',
        'status'
    ];
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    public function routeNotificationForTwilio()
    {
        return "+1" . $this->attributes['user_mobile'];
    }
    public function setPhoneNumberAttribute($value)
    {
        $this->attributes['user_mobile'] = $value;
    }
}
