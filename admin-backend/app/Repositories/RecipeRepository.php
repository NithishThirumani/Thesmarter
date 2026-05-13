<?php 
namespace App\Repositories;

use App\MerchantProductIngredient;
use App\MerchantProductRecipe;

class RecipeRepository
{
    public function getAllRecipes()
    {
        return MerchantProductRecipe::all();
    }

    public function getRecipeById($id)
    {
        return MerchantProductRecipe::find($id);
    }

    public function getRecipeByProductId($productId)
    {
        return MerchantProductRecipe::with('ingredients.ingredientDetails','ingredients.metricUnit')->where('product_id', $productId)->first();
    }

    public function createRecipe($data)
    {
        return MerchantProductRecipe::create($data);
    }

    public function updateRecipe($productId, $data)
    {
        return MerchantProductRecipe::where('product_id', $productId)->update($data);
    }

    public function deleteRecipe($id)
    {
        
        
        $this->deleteIngredients($id);
        return MerchantProductRecipe::where('product_id', $id)->delete();
        
    }
    function deleteIngredients($id)
    {
        return MerchantProductIngredient::where('product_id', $id)->delete();
    }
    public function getIngredientsByProductId($productId)
    {
        return MerchantProductIngredient::where('product_id', $productId)->get();
    }

    public function getIngredientByProductAndIngredientId($productId, $ingredientId)
    {
        return MerchantProductIngredient::where('product_id', $productId)
            ->where('ingredient_id', $ingredientId)
            ->first();
    }

    public function addIngredient($productId, $ingredient)
    {
        return MerchantProductIngredient::create([
            'product_id' => $productId,
            'ingredient_id' => $ingredient['ingredient_id'],
            'quantity' => $ingredient['quantity'],
            'measuring_unit_id' => $ingredient['measuring_unit_id'],
            'created_by' => 1,
        ]);
    }

    public function updateIngredient($id,$productId, $data)
    {
        
        return MerchantProductIngredient::where('ingredient_id', $id)
        ->where('product_id', $productId)
        ->update($data);
     
    }

    public function removeIngredients($productId, $ingredientIds)
    {
        return MerchantProductIngredient::where('product_id', $productId)
            ->whereIn('ingredient_id', $ingredientIds)
            ->delete();
    }
}
