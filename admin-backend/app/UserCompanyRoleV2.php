<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCompanyRoleV2 extends Model
{
    protected $table = 'user_company_roles_v2';

    protected $fillable = [
        'user_id',
        'company_id',
        'role_id',
        'status',
    ];

    public function role()
    {
        return $this->belongsTo(RoleV2::class, 'role_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id', 'company_id');
    }
}
