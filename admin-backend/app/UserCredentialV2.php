<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCredentialV2 extends Model
{
    protected $table = 'user_credentials_v2';

    protected $fillable = [
        'user_id',
        'login_type',
        'login_value',
        'password_hash',
    ];

    public function user()
    {
        return $this->belongsTo(UserLogin::class, 'user_id', 'user_id');
    }
}
