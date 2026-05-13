<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserContact extends Model
{
    protected $table = "user_contact";

    protected $fillable = [
        'user_id',
        'contact_id',
        'contact_type',
        'default_contact',
    ];
    public $timestamps = false;

    public function contact()
    {
        return $this->belongsTo(ContactDetail::class, 'contact_id', 'contact_id');
    }
    public function userDetails()
    {
        return $this->belongsTo('App\UserDetail', 'user_id', 'user_id');
    }
    public function order()
    {
        return $this->belongsTo('App\OrderDetail', 'user_id', 'customer_id');
    }
    public function contactDetails()
    {
        return $this->hasOne(ContactDetail::class, 'contact_id', 'contact_id');
    }
}
