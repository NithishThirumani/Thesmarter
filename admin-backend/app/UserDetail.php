<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $table = 'user_detail';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'first_name',
        'last_name',
        'user_dob',
        'marital_status',
        'user_gender',
        'user_status',
        'created_company_id'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function login()
    {
        return $this->hasOne(UserLogin::class, 'user_id', 'user_id');
    }
    public function userCompanies()
    {
        return $this->hasMany(UserCompanies::class, 'user_id', 'user_id');
    }
    public function contacts()
    {
        return $this->hasMany(UserContact::class, 'user_id', 'user_id');
    }
    public function defaultContactUser()
    {
        return $this->hasOne(UserContact::class, 'user_id', 'user_id')
            ->where('default_contact', 1);
    }
    public function additionalDetails()
    {
        return $this->hasOne(UserAdditionalDetail::class, 'user_id', 'user_id');
    }
    public function images()
    {
        return $this->belongsToMany(Images::class, 'user_images', 'user_id', 'image_id')
            ->withPivot('is_primary');
    }
}
