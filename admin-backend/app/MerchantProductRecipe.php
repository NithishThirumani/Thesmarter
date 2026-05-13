<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantProductRecipe extends Model
{
    protected $table ="merchant_product_recipe";
    protected $primary_key ="pr_id";
    protected $fillable = [
        'product_id',
        'recipe_type',
        'recipe_status',
    ];
    public function ingredients()
    {
        return $this->hasMany(MerchantProductIngredient::class, 'product_id', 'product_id');
    }
    const CREATED_AT = 'created_dtm';
    const UPDATED_AT = 'updated_dtm';
}
