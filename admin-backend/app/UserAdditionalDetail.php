<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAdditionalDetail extends Model 
{
    use HasFactory;
    protected $fillable = ['user_id', 'details', 'is_printable'];

    protected $casts = [
        'details' => 'array', // Automatically handle JSON conversion
        'is_printable' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(UserDetail::class,'user_id','user_id');
    }   
}
