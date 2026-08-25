<?php

namespace App\Actions;

use App\Enums\DiscountType;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductionStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Cake;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use App\Support\OrderTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePosOrder
{
    public function __construct(
        private AdjustInventoryForOrder $inventory,
        private GenerateInvoice $generateInvoice,
    ) {}

    /**
     * @param  array{
     *     user_id: int,
     *     payment_method: string,
     *     discount_type?: string|null,
     *     discount_value?: int|float|string|null,
     *     notes?: string|null,
     *     lines: list<array{cake_id?: int|null, name?: string|null, quantity: int, unit_price_rupees?: int|float|string|null}>
     * }  $details
     */
    public function handle(array $details): Order
    {
        $user = User::query()->findOrFail($details['user_id']);

        if (! $user->isCustomer()) {
            throw ValidationException::withMessages([
                'user_id' => 'Pick a customer for this POS sale.',
            ]);
        }

        $lines = collect($details['lines'] ?? [])
            ->map(function (array $line): array {
                $cake = isset($line['cake_id']) && $line['cake_id']
                    ? Cake::query()->find($line['cake_id'])
                    : null;

                $name = $cake?->name ?? trim((string) ($line['name'] ?? ''));
                $quantity = max(1, (int) ($line['quantity'] ?? 1));
                $unitPrice = $cake?->price ?? Money::rupeesToCents($line['unit_price_rupees'] ?? 0);

                return [
                    'cake_id' => $cake?->id,
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            })
            ->filter(fn (array $line): bool => $line['name'] !== '' && $line['unit_price'] >= 0)
            ->values();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Add at least one cake or ad-hoc item.',
            ]);
        }

        $itemsSubtotal = $lines->sum(fn (array $line): int => $line['unit_price'] * $line['quantity']);
        $discountType = DiscountType::tryFrom($details['discount_type'] ?? '') ?? DiscountType::Fixed;
        $discountValue = max(0, (float) ($details['discount_value'] ?? 0));
        $discountAmount = $this->discountAmount($itemsSubtotal, $discountType, $discountValue);
        $payableSubtotal = max(0, $itemsSubtotal - $discountAmount);

        $totals = OrderTotals::calculate($payableSubtotal, fulfillment: FulfillmentMethod::Pickup);
        $paymentMethod = PaymentMethod::tryFrom($details['payment_method'] ?? '') ?? PaymentMethod::Cash;

        try {
            return DB::transaction(function () use ($user, $lines, $details, $totals, $discountType, $discountValue, $discountAmount, $paymentMethod, $itemsSubtotal): Order {
                $order = Order::query()->create([
                    'user_id' => $user->id,
                    'order_source' => OrderSource::Manual,
                    'origin' => OrderOrigin::Catalog,
                    'fulfillment_method' => FulfillmentMethod::Pickup,
                    'status' => OrderStatus::Confirmed,
                    'production_status' => ProductionStatus::Ready,
                    'subtotal' => $itemsSubtotal,
                    'addons_total' => 0,
                    'delivery_fee' => $totals['delivery_fee'],
                    'tax_amount' => $totals['tax_amount'],
                    'deposit_paid' => $totals['deposit_paid'],
                    'total_due' => max(0, $totals['total_due']),
                    'discount_type' => $discountType,
                    'discount_value' => $discountType === DiscountType::Percent
                        ? (int) round($discountValue)
                        : Money::rupeesToCents($discountValue),
                    'discount_amount' => $discountAmount,
                    'payment_method' => $paymentMethod,
                    'receipt_number' => 'RCP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                    'delivery_date' => now()->toDateString(),
                    'delivery_address' => 'POS / counter pickup',
                    'notes' => $details['notes'] ?? null,
                ]);

                // Recompute total_due after discount: tax was based on discounted subtotal already in $totals
                $order->update([
                    'total_due' => max(0, $totals['total_due']),
                ]);

                foreach ($lines as $line) {
                    $order->items()->create([
                        'cake_id' => $line['cake_id'],
                        'name' => $line['name'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'selections' => [
                            'origin' => OrderOrigin::Catalog->value,
                            'order_source' => OrderSource::Manual->value,
                            'pos' => true,
                        ],
                    ]);
                }

                $this->inventory->applyConfirmation($order->fresh(['items.cake.recipes.items']));
                $this->generateInvoice->handle($order->fresh(['user', 'items']));

                return $order->fresh(['user', 'items', 'invoice']);
            });
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages([
                'lines' => $exception->getMessage(),
            ]);
        }
    }

    private function discountAmount(int $itemsSubtotal, DiscountType $type, float $value): int
    {
        if ($type === DiscountType::Percent) {
            return (int) round($itemsSubtotal * (min(100, $value) / 100));
        }

        return min($itemsSubtotal, Money::rupeesToCents($value));
    }
}
