<?php

namespace Database\Factories;

use App\Models\ShopSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopSetting>
 */
class ShopSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_fee' => 50000,
            'pickup_fee' => 0,
            'tax_percent' => 0,
            'deposit_percent' => 0,
            'labor_overhead_percent' => 15,
            'monthly_revenue_budget' => 50000000,
            'business_name' => 'Rushq cakes by Shashi',
            'business_address' => 'Colombo, Sri Lanka',
            'business_phone' => '0767681678',
            'business_email' => 'hello@rushqcakes.test',
        ];
    }
}
