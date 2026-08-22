<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    /**
     * @param  array{delivery_date: string, delivery_address: string, notes?: string|null}  $details
     */
    public function handle(User $user, array $details): Order
    {
        $items = CartItem::query()
            ->whereBelongsTo($user)
            ->with(['cake', 'cakeDesign'])
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        return DB::transaction(function () use ($user, $details, $items): Order {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'subtotal' => $items->sum(fn (CartItem $item): int => $item->lineTotal()),
                'delivery_date' => $details['delivery_date'],
                'delivery_address' => $details['delivery_address'],
                'notes' => $details['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'cake_id' => $item->cake_id,
                    'cake_design_id' => $item->cake_design_id,
                    'name' => $item->displayName(),
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'selections' => $item->cakeDesign?->selections,
                ]);
            }

            CartItem::query()->whereBelongsTo($user)->delete();

            return $order;
        });
    }
}
