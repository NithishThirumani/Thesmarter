<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserAccesPermissions extends Model
{
    //
    protected $table = 'user_access_permissions';
    protected $primaryKey = 'uap';
    protected $fillable = [
        'user_id',
        'module_id',
        'Create_priv',
        'Update_priv',
        'Read_priv',
        'Delete_priv',
        'Access_priv'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';


    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
