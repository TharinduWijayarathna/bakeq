<?php

namespace App\Actions;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    /**
     * @param  array{
     *     delivery_date: string,
     *     delivery_address: string,
     *     notes?: string|null,
     *     fulfillment_method?: string|null
     * }  $details
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

        $fulfillment = FulfillmentMethod::tryFrom($details['fulfillment_method'] ?? 'delivery')
            ?? FulfillmentMethod::Delivery;

        $itemsSubtotal = $items->sum(fn (CartItem $item): int => $item->lineTotal());
        $totals = OrderTotals::calculate($itemsSubtotal, fulfillment: $fulfillment);

        $origin = $items->contains(fn (CartItem $item): bool => $item->cake_design_id !== null)
            ? OrderOrigin::AiDesigner
            : OrderOrigin::Catalog;

        return DB::transaction(function () use ($user, $details, $items, $totals, $fulfillment, $origin): Order {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_source' => OrderSource::Online,
                'origin' => $origin,
                'fulfillment_method' => $fulfillment,
                'status' => OrderStatus::Pending,
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

            foreach ($items as $item) {
                $selections = $item->cakeDesign?->selections;

                if (is_array($selections)) {
                    $selections['origin'] = $origin->value;
                }

                $order->items()->create([
                    'cake_id' => $item->cake_id,
                    'cake_design_id' => $item->cake_design_id,
                    'name' => $item->displayName(),
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'selections' => $selections,
                ]);
            }

            CartItem::query()->whereBelongsTo($user)->delete();

            return $order;
        });
    }
}
