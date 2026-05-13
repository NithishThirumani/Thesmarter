<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    //
    protected $table = 'comments';
    protected $primaryKey = 'comment_id';
    protected $fillable = [
        'user_id',
        'note_id',
        'comment',
        'parent_comment_id'
    ];

    public function user()
    {
        return $this->belongsTo('App\UserDetail','user_id','user_id');
    }
}
