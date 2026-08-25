<?php

namespace Database\Factories;

use App\Models\GalleryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'image_path' => '/images/previews/preview-'.fake()->numberBetween(1, 6).'.jpg',
            'sort' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
