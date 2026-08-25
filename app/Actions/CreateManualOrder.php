<?php

namespace App\Actions;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Cake;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateManualOrder
{
    /**
     * @param  array{
     *     user_id: int,
     *     cake_id: int,
     *     quantity: int,
     *     delivery_date: string,
     *     delivery_address: string,
     *     notes?: string|null,
     *     fulfillment_method?: string|null,
     *     origin?: string|null
     * }  $details
     */
    public function handle(array $details): Order
    {
        $user = User::query()->findOrFail($details['user_id']);
        $cake = Cake::query()->findOrFail($details['cake_id']);
        $quantity = max(1, (int) $details['quantity']);

        if (! $user->isCustomer()) {
            throw ValidationException::withMessages([
                'user_id' => 'Pick a customer account for this walk-in order.',
            ]);
        }

        $fulfillment = FulfillmentMethod::tryFrom($details['fulfillment_method'] ?? 'pickup')
            ?? FulfillmentMethod::Pickup;

        $origin = OrderOrigin::tryFrom($details['origin'] ?? 'catalog')
            ?? OrderOrigin::Catalog;

        $itemsSubtotal = $cake->price * $quantity;
        $totals = OrderTotals::calculate($itemsSubtotal, fulfillment: $fulfillment);

        return DB::transaction(function () use ($user, $cake, $quantity, $details, $totals, $fulfillment, $origin): Order {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_source' => OrderSource::Manual,
                'origin' => $origin,
                'fulfillment_method' => $fulfillment,
                'status' => OrderStatus::Confirmed,
                'subtotal' => $totals['subtotal'],
                'addons_total' => $totals['addons_total'],
                'delivery_fee' => $totals['delivery_fee'],
                'tax_amount' => $totals['tax_amount'],
                'deposit_paid' => $totals['deposit_paid'],
                'total_due' => $totals['total_due'],
                'delivery_date' => $details['delivery_date'],
                'delivery_address' => $details['delivery_address'],
                'notes' => $details['notes'] ?? null,
            ]);

            $order->items()->create([
                'cake_id' => $cake->id,
                'name' => $cake->name,
                'quantity' => $quantity,
                'unit_price' => $cake->price,
                'selections' => [
                    'origin' => $origin->value,
                    'order_source' => OrderSource::Manual->value,
                ],
            ]);

            return $order;
        });
    }
}
