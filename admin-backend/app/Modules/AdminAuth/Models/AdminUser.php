<?php

namespace App\Modules\AdminAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminUser extends Model
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    protected $table = 'admin_users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'email',
        'name',
        'phone_number',
        'role',
        'pin_hash',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['pin_hash'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function otps()
    {
        return $this->hasMany(AdminOtp::class, 'admin_id');
    }

    public function refreshTokens()
    {
        return $this->hasMany(AdminRefreshToken::class, 'admin_id');
    }
}
