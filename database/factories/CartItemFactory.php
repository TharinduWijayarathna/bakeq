<?php

namespace Database\Factories;

use App\Models\Cake;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cake = Cake::factory();

        return [
            'user_id' => User::factory(),
            'cake_id' => $cake,
            'quantity' => 1,
            'unit_price' => 450000,
        ];
    }
}
