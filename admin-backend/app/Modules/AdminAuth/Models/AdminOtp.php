<?php

namespace App\Modules\AdminAuth\Models;

use Illuminate\Database\Eloquent\Model;

class AdminOtp extends Model
{
    protected $table = 'admin_otp';

    const MAX_ATTEMPTS = 5;

    const EXPIRY_MINUTES = 5;

    protected $fillable = ['admin_id', 'otp_hash', 'expires_at', 'is_verified', 'attempt_count'];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAttemptLimitReached(): bool
    {
        return $this->attempt_count >= self::MAX_ATTEMPTS;
    }
}
