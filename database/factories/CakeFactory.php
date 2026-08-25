<?php

namespace Database\Factories;

use App\Models\Cake;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cake>
 */
class CakeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'care_instructions' => 'Keep refrigerated. Best within 48 hours.',
            'note' => 'Buttercream • fresh finish',
            'price' => 450000,
            'base_price' => 450000,
            'per_tier_addon' => 150000,
            'per_flavor_addon' => 40000,
            'optional_addons' => [
                ['name' => 'Fresh florals', 'price' => 120000],
            ],
            'serves' => '8-10',
            'size_options' => [
                ['label' => '1 kg', 'servings' => '8-10', 'price' => 450000],
                ['label' => '2 kg', 'servings' => '18-22', 'price' => 750000],
            ],
            'ingredients' => ['Flour', 'Sugar', 'Eggs', 'Butter'],
            'allergens' => ['Gluten', 'Eggs', 'Dairy'],
            'lead_days' => 3,
            'image_path' => '/images/cakes/birthday.jpg',
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
