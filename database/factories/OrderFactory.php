<?php

namespace Database\Factories;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_source' => OrderSource::Online,
            'origin' => OrderOrigin::Catalog,
            'fulfillment_method' => FulfillmentMethod::Delivery,
            'status' => OrderStatus::Pending,
            'production_status' => ProductionStatus::Planning,
            'subtotal' => 450000,
            'addons_total' => 0,
            'delivery_fee' => 50000,
            'tax_amount' => 0,
            'deposit_paid' => 0,
            'total_due' => 500000,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_amount' => 0,
            'delivery_date' => now()->addDays(3)->toDateString(),
            'delivery_address' => fake()->address(),
            'notes' => null,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_source' => OrderSource::Manual,
            'fulfillment_method' => FulfillmentMethod::Pickup,
        ]);
    }

    public function aiDesigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'origin' => OrderOrigin::AiDesigner,
        ]);
    }
}
