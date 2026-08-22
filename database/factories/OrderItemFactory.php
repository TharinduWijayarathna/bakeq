<?php

namespace Database\Factories;

use App\Models\Cake;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'cake_id' => Cake::factory(),
            'name' => 'Classic Birthday Cake',
            'quantity' => 1,
            'unit_price' => 450000,
            'selections' => null,
        ];
    }
}
