<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCompanies extends Model
{
    protected $table = 'user_companies';
    protected $fillable = [
        'company_id',
        'user_id',
        'user_type',
        'status',
        'creator_id',
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function company()
    {
        return $this->belongsTo(CompanyDetail::class,'company_id','company_id');
    }

    public function detail()
    {
        return $this->belongsTo(UserDetail::class, 'user_id', 'user_id');
    }

    public function userLogin()
    {
        return $this->belongsTo(UserLogin::class, 'user_id', 'user_id');
    }
}
