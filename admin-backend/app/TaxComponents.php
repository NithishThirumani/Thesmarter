<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxComponents extends Model
{
    protected $table = 'tax_components';
    protected $primaryKey = 'tc_id';
    protected $fillable = [
        'tax_id',
        'component_name',

    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
    public function details()
    {
        return $this->hasMany(TaxDetail::class,'tc_id','tc_id');
    }
}
