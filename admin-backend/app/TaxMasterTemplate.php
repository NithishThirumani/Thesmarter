<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxMasterTemplate extends Model
{
    protected $table = 'tax_master_template';

    protected $primaryKey = 'template_tax_id';

    protected $fillable = [
        'country_code',
        'region_type',
        'region_code',
        'tax_type',
        'applicability_type',
        'tax_name',
        'is_active',
        'version',
    ];

    const CREATED_AT = 'created_dtm';

    const UPDATED_AT = 'updated_dtm';

    protected $casts = [
        'is_active' => 'integer',
        'version' => 'integer',
    ];

    public function components()
    {
        return $this->hasMany(TaxComponentTemplate::class, 'template_tax_id', 'template_tax_id');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $q */
    public function scopeForCountry($q, string $countryCode)
    {
        return $q->where('country_code', strtoupper($countryCode))->where('is_active', 1);
    }
}
