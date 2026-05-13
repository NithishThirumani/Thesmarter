<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $table = 'transaction_detail';
    protected $primaryKey = 'trans_id';
    protected $fillable = [
        'executive_id',
        'branch_id',
        'trans_type',
        'trans_amount',
        'trans_op_id'
    ];
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
