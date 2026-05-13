<?php
namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceDetail extends Model
{
    use HasFactory;

    protected $table = 'attendance_detail';
    protected $primaryKey = 'ad_id';
    protected $fillable = ['user_id', 'branch_id','punch_in', 'punch_out', 'latitude', 'longitude'];

    public function user()
    {
        return $this->belongsTo(UserDetail::class);
    }
}
