<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantProductIngredient extends Model
{
    protected $table = 'merchant_product_ingredients';
    protected $fillable = ['product_id', 'ingredient_id', 'quantity', 'unit_id', 'created_by'];

    // Define relationship with MerchantIngredient
    public function ingredientDetails()
    {
        return $this->belongsTo(MerchantIngredients::class, 'ingredient_id', 'ingredient_id');
    }
    public function metricUnit()
    {
        return $this->belongsTo(MetricUnits::class, 'unit_id', 'unit_id');
    }


    const CREATED_AT = 'created_on';
    const UPDATED_AT = 'updated_dtm';
}
