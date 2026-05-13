<?php 
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecipeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => 'required',
            'recipe_type' => 'required|in:L,D',
            'recipe_status' => 'required|in:A,D',
            'ingredients' => 'array',
            // 'ingredients.*.ingredient_id' => 'exists:ingredient_table,id',
            // 'ingredients.*.quantity' => 'required|numeric',
            // 'ingredients.*.measuring_unit_id' => 'required|exists:measuring_units,id',
        ];
    }
    public function messages()
    {
        return [
            'product_id.required' => 'Product ID is required',
            // 'product_id.exists' => 'Invalid Product ID',
            'recipe_type.required' => 'Recipe Type is required',
            'recipe_type.in' => 'Recipe Type must be L (Live) or D (Daily)',
            'recipe_status.required' => 'Recipe Status is required',
            'recipe_status.in' => 'Recipe Status must be A (Active) or D (Deactivated)',
            // 'ingredients.required' => 'Ingredients are required',
            // 'ingredients.*.ingredient_id.required' => 'Each ingredient must have an ID',
            // 'ingredients.*.ingredient_id.exists' => 'Invalid Ingredient ID',
            // 'ingredients.*.quantity.required' => 'Quantity is required',
            // 'ingredients.*.quantity.min' => 'Quantity must be at least 0',
            // 'ingredients.*.measuring_unit_id.required' => 'Measuring Unit is required',
            // 'ingredients.*.measuring_unit_id.exists' => 'Invalid Measuring Unit ID',
        ];
    }
}
