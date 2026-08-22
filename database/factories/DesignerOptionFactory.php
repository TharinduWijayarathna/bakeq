<?php

namespace Database\Factories;

use App\Models\DesignerOption;
use App\Models\DesignerOptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesignerOption>
 */
class DesignerOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'designer_option_group_id' => DesignerOptionGroup::factory(),
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(4),
            'color_hex' => fake()->hexColor(),
            'extra_price' => 0,
            'image_path' => null,
            'sort' => 0,
            'is_active' => true,
        ];
    }
}
