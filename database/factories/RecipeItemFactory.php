<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeItem>
 */
class RecipeItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity' => 250,
        ];
    }
}
