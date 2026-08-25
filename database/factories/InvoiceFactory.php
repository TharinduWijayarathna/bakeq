<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('########'),
            'issued_at' => now(),
            'subtotal' => 450000,
            'discount_amount' => 0,
            'delivery_fee' => 50000,
            'tax_amount' => 0,
            'deposit_paid' => 0,
            'total_due' => 500000,
            'line_items' => [
                ['name' => 'Classic Birthday Cake', 'quantity' => 1, 'unit_price' => 450000, 'line_total' => 450000],
            ],
            'business_snapshot' => [
                'name' => 'Rushq cakes by Shashi',
                'address' => 'Colombo',
                'phone' => '0767681678',
                'email' => 'hello@rushqcakes.test',
            ],
            'customer_snapshot' => [
                'name' => 'Customer',
                'email' => 'customer@example.com',
                'phone' => null,
                'address' => 'Colombo',
            ],
        ];
    }
}
