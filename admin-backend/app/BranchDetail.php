<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BranchDetail extends Model
{
    protected $table = 'branch_detail';
    protected $primaryKey = 'branch_id';
    protected $fillable = [
        'company_id',
        'branch_name',
        'latitude',
        'longitude',
        'branch_status',
        'contact_id',
        'branch_type',
        'work_type'
    ];
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
    const CREATED_AT = 'create_dtm';
    const UPDATED_AT = 'updated_dtm';
    public function contact()
    {
        return $this->belongsTo(ContactDetail::class, 'contact_id', 'contact_id');
    }
}
