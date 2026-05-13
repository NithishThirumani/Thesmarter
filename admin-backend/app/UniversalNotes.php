<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UniversalNotes extends Model
{
    protected $table = 'universal_notes';
    protected $fillable = [
        'user_id',
        'reference_id',
        'reference_type',
        'note',
        'created_id',
        'updated_id',
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function comments()
    {
        return $this->hasMany('App\Comments', 'note_id', 'note_id');
    }
}
