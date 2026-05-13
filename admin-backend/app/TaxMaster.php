<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxMaster extends Model
{
    protected $table = 'tax_master';
    protected $primaryKey = 'tax_id';
    protected $fillable = [
        'company_id',
        'template_tax_id',
        'tax_name',

    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function components()
    {
        return $this->hasMany(TaxComponents::class, 'tax_id','tax_id');
    }
}
