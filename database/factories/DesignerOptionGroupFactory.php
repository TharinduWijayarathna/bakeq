<?php

namespace Database\Factories;

use App\Enums\SelectionType;
use App\Models\DesignerOptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DesignerOptionGroup>
 */
class DesignerOptionGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('##'),
            'selection_type' => SelectionType::Single,
            'is_required' => true,
            'min_select' => 1,
            'max_select' => 1,
            'sort' => 0,
            'is_active' => true,
        ];
    }
}
