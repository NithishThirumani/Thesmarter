<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactDetail extends Model
{
    protected $table = 'contact_detail';
    protected $primaryKey = "contact_id";
    protected $fillable = [
        'company_id',
        'phone',
        'email',
        'pincode',
        'city',
        'state',
        'country',
        'address1',
        'area',
        'longitude',
        'latitude'
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function user()
    {
        return $this->hasOne('App\UserContact', 'contact_id', 'contact_id');
    }
     public function userContact()
    {
        return $this->hasOne(UserContact::class, 'contact_id','contact_id');
    }
}
