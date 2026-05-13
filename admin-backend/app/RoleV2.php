<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleV2 extends Model
{
    protected $table = 'roles_v2';

    protected $fillable = [
        'role_name',
        'role_type',
        'company_id',
    ];

    public function companyRoles()
    {
        return $this->hasMany(UserCompanyRoleV2::class, 'role_id', 'id');
    }
}
