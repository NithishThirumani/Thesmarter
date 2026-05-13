<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaSequence extends Model
{
    protected $table = 'proforma_sequences';
    protected $primaryKey = 'id';
    protected $fillable = [
        'company_id',
        'year',
        'sequence_number',
    ];
    public $timestamps = false;
}
