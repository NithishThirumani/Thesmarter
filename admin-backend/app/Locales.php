<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locales extends Model
{
    use HasFactory;

    protected $table = "locales";
    protected $fillable = [
        'code',
        'name',
        'direction'
    ];

    public function companies()
    {
        return $this->hasMany(CompanyLocales::class, 'locale_id', 'id');
    }
}
