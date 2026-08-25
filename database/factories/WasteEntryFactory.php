<?php

namespace Database\Factories;

use App\Enums\WasteReason;
use App\Models\Ingredient;
use App\Models\WasteEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteEntry>
 */
class WasteEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wasted_on' => now()->toDateString(),
            'ingredient_id' => Ingredient::factory(),
            'cake_id' => null,
            'quantity' => 100,
            'reason' => WasteReason::Spoilage,
            'cost_impact' => 5000,
            'notes' => null,
        ];
    }
}
