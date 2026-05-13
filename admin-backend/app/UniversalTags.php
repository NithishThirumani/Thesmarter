<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UniversalTags extends Model
{
    protected $table = 'universal_tags';
    protected $primaryKey = 'tag_id';
    protected $fillable = [
        'company_id',
        'module_id',
        'tag_name',
        'created_by'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
