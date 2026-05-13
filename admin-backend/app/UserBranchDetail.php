<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserBranchDetail extends Model
{
    protected $table = 'user_branch_detail';

    protected $fillable = [
        'user_id',
        'branch_id',
        'company_id',
        'user_branch_status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /** DB table has `created_dtm` only — no `updated_dtm` column. */
    const CREATED_AT = 'created_dtm';

    const UPDATED_AT = null;
}
