<?php

namespace Database\Factories;

use App\Enums\IngredientUnit;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'unit' => IngredientUnit::Grams,
            'current_stock' => 5000,
            'unit_cost' => 2,
            'supplier' => 'Colombo Foods',
            'reorder_threshold' => 1000,
            'expiry_date' => now()->addMonths(3)->toDateString(),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => 100,
            'reorder_threshold' => 500,
        ]);
    }
}
