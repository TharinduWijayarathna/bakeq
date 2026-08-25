<?php

namespace Database\Factories;

use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialPost>
 */
class SocialPostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform' => fake()->randomElement(['tiktok', 'instagram', 'facebook']),
            'title' => fake()->sentence(4),
            'url' => 'https://www.instagram.com/p/example/',
            'embed_html' => null,
            'image_path' => '/images/previews/preview-'.fake()->numberBetween(1, 6).'.jpg',
            'posted_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'is_active' => true,
            'sort' => fake()->numberBetween(0, 20),
        ];
    }
}
