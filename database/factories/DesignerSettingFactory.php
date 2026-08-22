<?php

namespace Database\Factories;

use App\Models\DesignerSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesignerSetting>
 */
class DesignerSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'min_tiers' => 1,
            'max_tiers' => 3,
            'lead_days' => 3,
            'notice' => 'We take custom cakes a few days ahead so every layer is baked fresh.',
            'base_price' => 450000,
        ];
    }
}
