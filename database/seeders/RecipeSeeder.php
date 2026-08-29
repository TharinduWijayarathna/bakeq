<?php

namespace Database\Seeders;

use App\Models\Cake;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cakes = Cake::all();
        $ingredients = Ingredient::all();
        
        if ($ingredients->isEmpty()) {
            return;
        }

        foreach ($cakes as $cake) {
            foreach ($cake->size_options as $option) {
                $recipe = Recipe::factory()->create([
                    'cake_id' => $cake->id,
                    'name' => $cake->name . ' - ' . $option['label'],
                    'size_label' => $option['label'],
                ]);
                
                // Add 3-5 random ingredients
                $randomIngredients = $ingredients->random(rand(3, 5));
                foreach ($randomIngredients as $ingredient) {
                    RecipeItem::factory()->create([
                        'recipe_id' => $recipe->id,
                        'ingredient_id' => $ingredient->id,
                        'quantity' => rand(1, 5) * 50, // e.g. 50, 100, ..., 250
                    ]);
                }
            }
        }
    }
}
