<?php

namespace App\Actions;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ProductionStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderTotals;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(private GenerateInvoice $generateInvoice) {}

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

        $origin = $this->resolveOrigin($items);
        $designNotes = $this->collectDesignNotes($items);
        $orderNotes = trim(implode("\n", array_filter([
            filled($details['notes'] ?? null) ? trim((string) $details['notes']) : null,
            $designNotes,
        ])));

        return DB::transaction(function () use ($user, $details, $items, $totals, $fulfillment, $origin, $orderNotes): Order {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_source' => OrderSource::Online,
                'origin' => $origin,
                'fulfillment_method' => $fulfillment,
                'status' => OrderStatus::Pending,
                'production_status' => ProductionStatus::Planning,
                'subtotal' => $totals['subtotal'],
                'addons_total' => $totals['addons_total'],
                'delivery_fee' => $totals['delivery_fee'],
                'tax_amount' => $totals['tax_amount'],
                'deposit_paid' => $totals['deposit_paid'],
                'total_due' => $totals['total_due'],
                'delivery_date' => $details['delivery_date'],
                'delivery_address' => $details['delivery_address'],
                'notes' => $orderNotes !== '' ? $orderNotes : null,
            ]);

            foreach ($items as $item) {
                $selections = $item->cakeDesign?->selections;

                if (is_array($selections)) {
                    $itemOrigin = OrderOrigin::tryFrom((string) ($selections['origin'] ?? '')) ?? $origin;
                    $selections['origin'] = $itemOrigin->value;
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

            $this->generateInvoice->handle($order->fresh(['user', 'items']));

            return $order->fresh(['items', 'invoice']);
        });
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function resolveOrigin($items): OrderOrigin
    {
        $hasRedesign = $items->contains(function (CartItem $item): bool {
            $selections = $item->cakeDesign?->selections ?? [];

            return ($selections['origin'] ?? null) === OrderOrigin::AiRedesign->value
                || ($selections['mode'] ?? null) === 'redesign';
        });

        if ($hasRedesign) {
            return OrderOrigin::AiRedesign;
        }

        if ($items->contains(fn (CartItem $item): bool => $item->cake_design_id !== null)) {
            return OrderOrigin::AiDesigner;
        }

        return OrderOrigin::Catalog;
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function collectDesignNotes($items): ?string
    {
        $notes = $items
            ->map(function (CartItem $item): ?string {
                $note = $item->cakeDesign?->selections['customer_notes'] ?? null;

                return filled($note) ? trim((string) $note) : null;
            })
            ->filter()
            ->unique()
            ->values();

        return $notes->isEmpty() ? null : $notes->implode("\n");
    }
}
