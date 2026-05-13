<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserLogin extends Authenticatable implements JWTSubject
{
    use Notifiable,HasApiTokens;
    protected $table = 'user_login';
    protected $primaryKey = 'user_id'; // Ensure this matches your primary key
    protected $fillable = [
        'user_id',
        'user_mobile',
        'email',
        'user_pin',
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    // Define the tokens relationship
    public function tokens()
    {
        return $this->morphMany(\Laravel\Sanctum\PersonalAccessToken::class, 'tokenable');
    }
    public function details()
    {
        return $this->belongsTo(UserDetail::class,'user_id','user_id');
    }
    public function companies()
    {
        return $this->belongsTo(UserCompanies::class, 'user_id', 'user_id');
    }
    public function contact()
    {
        return $this->hasMany(UserContact::class, 'user_id', 'user_id');
    }
    public function access()
    {
        return $this->hasMany(UserAccesPermissions::class, 'user_id', 'user_id');
    }
    public function routeNotificationForTwilio()
    {
        return "+91" . $this->attributes['user_mobile'];
    }
    public function setPhoneNumberAttribute($value)
    {
        $this->attributes['user_mobile'] = $value;
    }
    public function routeNotificationForSns($notification)
    {
        return $this->attributes['user_mobile'];
        // return '+919441482605';
    }
    public function order()
    {
        return $this->belongsTo('App\OrderDetail', 'user_id', 'customer_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
