<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyAppointments extends Model
{
    protected $table = 'company_appointments';
    protected $primaryKey = 'appointment_id';
    protected $fillable = [
        'user_id',
        'created_by',
        'walkin',
        'start',
        'end',
        'status_id',
        'viewed',
        'remarks',
        'company_id',
        'branch_id',
        'appointment_no'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';

    public function company(){
        return $this->belongsTo(CompanyDetail::class,'company_id','company_id');
    }
}
