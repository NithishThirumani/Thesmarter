<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LineOfBusiness extends Model
{
    protected $table = 'line_of_business';
    protected $primaryKey = 'lob_id';
    protected $fillable = [
        'lob_name',
        'lob_description',
        'lob_status',
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';
    
}
