<?php 

namespace App\Services;

use App\Repositories\RecipeRepository;
use Illuminate\Support\Facades\DB;

class RecipeService
{
    protected $recipeRepository;

    public function __construct(RecipeRepository $recipeRepository)
    {
        $this->recipeRepository = $recipeRepository;
    }

    public function getAllRecipes()
    {
        return $this->recipeRepository->getAllRecipes();
    }

    public function getRecipeById($id)
    {
   
        return $this->recipeRepository->getRecipeByProductId($id);
    }

    public function createOrUpdateRecipe($data)
    { 
        return DB::transaction(function () use ($data) {
            // Check if recipe already exists for the product
            $existingRecipe = $this->recipeRepository->getRecipeByProductId($data['product_id']);
          
            if ($existingRecipe) {
                // Update recipe type and status if needed
                $this->recipeRepository->updateRecipe($existingRecipe->product_id, [
                    'recipe_type' => $data['recipe_type'],
                    'recipe_status' => $data['recipe_status']
                ]);
               
                $recipe =  $this->recipeRepository->getRecipeByProductId($existingRecipe->product_id);
                
            } else {
                // Create new recipe
                $recipe = $this->recipeRepository->createRecipe($data);
            }
            
            // Process ingredients
            $this->addOrUpdateIngredients($data['product_id'], $data['ingredients']);
            $recipe =  $this->recipeRepository->getRecipeByProductId($existingRecipe->product_id);
            return $recipe;
        });
    }

    public function updateRecipe($product_id, $data)
    {
       
        return DB::transaction(function () use ($product_id, $data) {
            $recipe = $this->recipeRepository->getRecipeByProductId($product_id);

            if ($recipe) {
                // Update recipe only if changes exist
                if ($recipe->recipe_type !== $data['recipe_type'] || $recipe->recipe_status !== $data['recipe_status']) {
                    $this->recipeRepository->updateRecipe($product_id, [
                        'recipe_type' => $data['recipe_type'],
                        'recipe_status' => $data['recipe_status']
                    ]);
                    
                }

                // Update ingredients
                $this->addOrUpdateIngredients($recipe->product_id, $data['ingredients']);
            }
           
            $recipe =  $this->recipeRepository->getRecipeByProductId($recipe->product_id);
            return $recipe;
        });
    }

    public function deleteRecipe($product_id)
    {
        return $this->recipeRepository->deleteRecipe($product_id);
    }

    public function addOrUpdateIngredients($productId, $ingredients)
    {
        return DB::transaction(function () use ($productId, $ingredients) {
            $recipe = $this->recipeRepository->getRecipeByProductId($productId);

            if (!$recipe || $recipe->recipe_status !== 'A') {
                return false;
            }

            // Get current ingredients mapped to this product_id
            $existingIngredients = $this->recipeRepository->getIngredientsByProductId($productId);
            $existingIngredientIds = $existingIngredients->pluck('ingredient_id')->toArray();

            $newIngredientIds = [];
            
            foreach ($ingredients as $ingredient) {
                $newIngredientIds[] = $ingredient['ingredient_id'];

                $existingIngredient = $this->recipeRepository->getIngredientByProductAndIngredientId(
                    $productId, 
                    $ingredient['ingredient_id']
                );

                if ($existingIngredient) {
                    // Update existing ingredient
                    $this->recipeRepository->updateIngredient(
                        $existingIngredient->ingredient_id, 
                        $existingIngredient->product_id,
                        [
                            'quantity' => $ingredient['quantity'],
                            'unit_id' => $ingredient['measuring_unit_id']
                        ]
                    );
                } else {
                    // Create new ingredient entry
                    $this->recipeRepository->addIngredient($productId, $ingredient);
                }
            }

            // Remove ingredients that are no longer mapped to the product_id
            $ingredientsToDelete = array_diff($existingIngredientIds, $newIngredientIds);
            if (!empty($ingredientsToDelete)) {
                $this->recipeRepository->removeIngredients($productId, $ingredientsToDelete);
            }
         
            return true;
        });
    }
}
