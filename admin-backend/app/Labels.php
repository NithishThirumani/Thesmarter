<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Labels extends Model
{
    use HasFactory;

    protected $table = "labels";
    protected $fillable = [
        'code',
        'type'
    ];
}
