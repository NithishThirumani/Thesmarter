<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxComponentTemplate extends Model
{
    protected $table = 'tax_component_template';

    protected $primaryKey = 'template_tc_id';

    protected $fillable = [
        'template_tax_id',
        'component_name',
    ];

    const CREATED_AT = 'created_dtm';

    const UPDATED_AT = 'updated_dtm';

    public function master()
    {
        return $this->belongsTo(TaxMasterTemplate::class, 'template_tax_id', 'template_tax_id');
    }

    public function detailRows()
    {
        return $this->hasMany(TaxDetailTemplate::class, 'template_tc_id', 'template_tc_id');
    }
}
