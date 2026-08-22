<?php

namespace Database\Factories;

use App\Models\CakeDesign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CakeDesign>
 */
class CakeDesignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'selections' => ['type' => 'Birthday'],
            'tiers' => 1,
            'preview_path' => 'images/previews/preview-1.jpg',
            'estimated_price' => 450000,
        ];
    }
}
