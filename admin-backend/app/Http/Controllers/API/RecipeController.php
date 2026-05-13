<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecipeRequest;
use App\Http\Responses\RecipeResponse;
use App\Services\RecipeService;

class RecipeController extends Controller
{
    protected $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function index()
    {
        $recipes = $this->recipeService->getAllRecipes();
        // return response()->json($recipes);
        return RecipeResponse::success($recipes);
    }

    public function store(RecipeRequest $request)
    {
        try {
            $recipe = $this->recipeService->createOrUpdateRecipe($request->validated());
            return RecipeResponse::success($recipe, 'Recipe created/updated successfully');
        } catch (ValidationException $e) {
            return RecipeResponse::error('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            return RecipeResponse::error('Failed to create/update recipe', 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        $recipe = $this->recipeService->getRecipeById($id);
        if ($recipe) {
            return RecipeResponse::success($recipe);
        }

        return RecipeResponse::error('Recipe not found', 404);
    }

    /**
     * Update a recipe by Product ID
     */
    public function update(RecipeRequest $request, $id): JsonResponse
    {
        try {
            $recipe = $this->recipeService->updateRecipe($id, $request->validated());

            if (!$recipe) {
                return RecipeResponse::error('Recipe not found', 404);
            }

            return RecipeResponse::success($recipe, 'Recipe updated successfully');
        } catch (ValidationException $e) {
            return RecipeResponse::error('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            return RecipeResponse::error('Failed to update recipe', 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $this->recipeService->deleteRecipe($id);
        return RecipeResponse::success(null, 'Recipe deleted successfully');
    }
}
