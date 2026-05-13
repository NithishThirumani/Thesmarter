<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabelTranslations extends Model
{
    use HasFactory;

    protected $table = "label_translations";
    protected $fillable  = [
        'label_id',
        'locale_id',
        'name'
    ];
}
