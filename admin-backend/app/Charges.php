<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Charges extends Model
{
    //
    protected $table = 'miscellaneous_charges';
    protected $primaryKey = 'misc_id';
    protected $fillable = [
        'company_id',
        'charge_name',
        'charge_level',
        'charge_type',
        'charge_description',
        'operation_type',
        'charge_status'
    ];
}
