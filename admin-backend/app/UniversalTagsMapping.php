<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UniversalTagsMapping extends Model
{
    protected $table = 'universal_tags_mapping';
    protected $fillable = [
        'tag_id',
        'resource_id',
        'resource_module_id',
        'created_by',
        'tag_logo'
    ];
    const CREATED_AT = 'created_dtm';
}
