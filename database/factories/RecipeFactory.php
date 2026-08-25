<?php

namespace Database\Factories;

use App\Models\Cake;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cake_id' => Cake::factory(),
            'name' => null,
            'size_label' => '',
        ];
    }
}
