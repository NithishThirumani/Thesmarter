<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobsVaccancies extends Model
{
    protected $table = 'job_vaccancies';
    protected $fillable = [
        'code',
        'title',
        'description',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by'
    ];

    public function createdBy()
    {
        return $this->belongsTo('App\UserDetail', 'created_by', 'user_id');
    }

    public function company()
    {
        return $this->belongsTo('App\CompanyDetail', 'company_id', 'company_id');
    }

    public function branch()
    {
        return $this->belongsTo('App\BranchDetail', 'branch_id', 'branch_id');
    }
    protected $casts = [
        'description' => 'json', // or 'array'
    ];
}
