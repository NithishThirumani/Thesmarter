<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaxDetailTemplate extends Model
{
    protected $table = 'tax_detail_template';

    protected $primaryKey = 'template_td_id';

    protected $fillable = [
        'template_tc_id',
        'tax_value',
        'tax_start_date',
        'tax_end_date',
    ];

    const CREATED_AT = 'created_dtm';

    const UPDATED_AT = 'updated_dtm';

    protected $casts = [
        'tax_value' => 'float',
    ];

    public function component()
    {
        return $this->belongsTo(TaxComponentTemplate::class, 'template_tc_id', 'template_tc_id');
    }
}
