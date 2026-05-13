<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantIngredients extends Model
{
    protected $table ="merchant_ingredients";
    protected $primary_key ="ingredient_id";
    protected $fillable = [
        'company_id',
        'ingredient_name',
        'ingredient_brand',
        'ingredient_code',
        'ingredient_hsn_code',
        'ingredient_barcode',
        'unit_id',
        'ingredient_status'
    ];
    public function recipes()
    {
        return $this->hasMany(MerchantProductIngredient::class, 'ingredient_id', 'ingredient_id');
    }
   
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
