<?php

namespace App\Modules\AdminAuth\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRefreshToken extends Model
{
    protected $table = 'admin_refresh_tokens';

    protected $fillable = ['admin_id', 'token_hash', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
