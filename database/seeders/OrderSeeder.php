<?php

namespace Database\Seeders;

use App\Models\Cake;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $cakes = Cake::all();

        if ($users->isEmpty() || $cakes->isEmpty()) {
            return;
        }

        // Create 15 orders
        for ($i = 0; $i < 15; $i++) {
            $user = $users->random();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            // Add 1-2 items per order
            $itemCount = rand(1, 2);
            for ($j = 0; $j < $itemCount; $j++) {
                $cake = $cakes->random();
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'cake_id' => $cake->id,
                    'name' => $cake->name,
                    'unit_price' => $cake->price,
                ]);
            }
            
            // Re-calculate totals
            $subtotal = $order->items()->sum('unit_price');
            $order->update([
                'subtotal' => $subtotal,
                'total_due' => $subtotal + $order->delivery_fee,
            ]);
        }
    }
}
