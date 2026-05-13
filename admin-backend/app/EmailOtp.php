<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailOtp extends Model
{
    protected $table = 'email_otps';

    protected $fillable = [
        'user_id',
        'email',
        'otp_hash',
        'purpose',
        'expires_at',
        'ip'
    ];

    protected $dates = [
        'expires_at',
        'consumed_at',
        'created_at',
        'updated_at'
    ];

    public function isExpired(): bool
    {     
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return !is_null($this->consumed_at);
    }
}
