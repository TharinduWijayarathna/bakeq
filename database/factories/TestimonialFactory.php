<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote' => fake()->sentence(16),
            'author' => fake()->name(),
            'occasion' => fake()->randomElement(['Birthday order', 'Wedding cake', 'Cupcake box', 'Anniversary']),
            'rating' => 5,
            'sort' => 0,
            'is_active' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
