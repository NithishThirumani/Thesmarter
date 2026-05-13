<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyLocales extends Model
{
    use HasFactory;
    protected $table = "company_locales";
    protected $fillable = [
        'company_id',
        'label_id',
        'isDefault'
    ];
    public function companies()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id', 'company_id');
    }
    public function locale()
    {
        return $this->belongsTo(Locales::class, 'id', 'locale_id');
    }
}
