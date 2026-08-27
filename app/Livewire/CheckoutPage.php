<?php

namespace App\Livewire;

use App\Actions\CreateStripeCheckoutSession;
use App\Actions\PlaceOrder;
use App\Enums\CheckoutPaymentChoice;
use App\Enums\FulfillmentMethod;
use App\Models\CartItem;
use App\Models\DesignerSetting;
use App\Models\ShopSetting;
use App\Support\Money;
use App\Support\OrderTotals;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Checkout')]
class CheckoutPage extends Component
{
    public string $delivery_date = '';

    public string $delivery_address = '';

    public string $notes = '';

    public string $fulfillment_method = 'delivery';

    public string $payment_choice = 'pay_later';

    public function mount(): void
    {
        $settings = DesignerSetting::current();
        $user = auth()->user();
        $shop = ShopSetting::current();

        $this->delivery_date = now()->addDays($settings->lead_days)->toDateString();
        $this->delivery_address = trim(($user->address_line ?? '').', '.($user->city ?? ''), ', ');

        if ($shop->acceptsOnlinePayments()) {
            $this->payment_choice = CheckoutPaymentChoice::OnlineFull->value;
        }
    }

    public function placeOrder(PlaceOrder $placeOrder, CreateStripeCheckoutSession $createSession): void
    {
        $settings = DesignerSetting::current();
        $shop = ShopSetting::current();
        $items = CartItem::query()
            ->whereBelongsTo(auth()->user())
            ->with(['cake', 'cakeDesign'])
            ->get();
        $itemsSubtotal = $items->sum(fn (CartItem $item): int => $item->lineTotal());
        $fulfillment = FulfillmentMethod::tryFrom($this->fulfillment_method) ?? FulfillmentMethod::Delivery;
        $totals = OrderTotals::calculate($itemsSubtotal, fulfillment: $fulfillment, settings: $shop);
        $allowedChoices = array_column(
            $this->paymentChoiceOptions($shop, $totals['deposit_paid']),
            'value',
        );

        $validated = $this->validate([
            'delivery_date' => ['required', 'date', 'after_or_equal:'.now()->addDays($settings->lead_days)->toDateString()],
            'delivery_address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
            'payment_choice' => ['required', Rule::in($allowedChoices)],
        ]);

        $order = $placeOrder->handle(auth()->user(), $validated);
        $choice = CheckoutPaymentChoice::from($validated['payment_choice']);

        $this->dispatch('cart-updated');

        if ($choice->isOnline()) {
            $session = $createSession->handle($order, $choice, $order->payment_amount);

            $this->redirect($session['url']);

            return;
        }

        session()->flash('status', 'Order #'.$order->id.' placed. Pay at pickup or delivery — we will confirm shortly.');

        $this->redirect(route('orders.index'), navigate: true);
    }

    public function render(): View
    {
        $items = CartItem::query()
            ->whereBelongsTo(auth()->user())
            ->with(['cake', 'cakeDesign'])
            ->get();

        $itemsSubtotal = $items->sum(fn (CartItem $item): int => $item->lineTotal());
        $fulfillment = FulfillmentMethod::tryFrom($this->fulfillment_method) ?? FulfillmentMethod::Delivery;
        $shop = ShopSetting::current();
        $totals = OrderTotals::calculate($itemsSubtotal, fulfillment: $fulfillment, settings: $shop);
        $gross = $totals['subtotal'] + $totals['addons_total'] + $totals['delivery_fee'] + $totals['tax_amount'];
        $choice = CheckoutPaymentChoice::tryFrom($this->payment_choice) ?? CheckoutPaymentChoice::PayLater;
        $payNow = match ($choice) {
            CheckoutPaymentChoice::OnlineDeposit => $totals['deposit_paid'],
            CheckoutPaymentChoice::OnlineFull => $gross,
            CheckoutPaymentChoice::PayLater => 0,
        };

        $paymentChoices = $this->paymentChoiceOptions($shop, $totals['deposit_paid']);
        $allowedValues = array_column($paymentChoices, 'value');

        if (! in_array($this->payment_choice, $allowedValues, true)) {
            $this->payment_choice = $allowedValues[0] ?? CheckoutPaymentChoice::PayLater->value;
        }

        return view('livewire.checkout-page', [
            'items' => $items,
            'totals' => $totals,
            'formatted' => [
                'subtotal' => Money::format($totals['subtotal']),
                'addons_total' => Money::format($totals['addons_total']),
                'delivery_fee' => Money::format($totals['delivery_fee']),
                'tax_amount' => Money::format($totals['tax_amount']),
                'deposit_paid' => Money::format($totals['deposit_paid']),
                'total_due' => Money::format($totals['total_due']),
                'gross' => Money::format($gross),
                'pay_now' => Money::format($payNow),
            ],
            'payNowCents' => $payNow,
            'settings' => DesignerSetting::current(),
            'shop' => $shop,
            'fulfillmentMethods' => FulfillmentMethod::cases(),
            'paymentChoices' => $paymentChoices,
            'submitLabel' => match ($choice) {
                CheckoutPaymentChoice::OnlineDeposit => 'Pay deposit',
                CheckoutPaymentChoice::OnlineFull => 'Pay now',
                CheckoutPaymentChoice::PayLater => 'Place order',
            },
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function paymentChoiceOptions(ShopSetting $shop, int $depositCents): array
    {
        $options = [];

        if ($shop->acceptsOnlinePayments()) {
            if ($depositCents > 0) {
                $options[] = [
                    'value' => CheckoutPaymentChoice::OnlineDeposit->value,
                    'label' => CheckoutPaymentChoice::OnlineDeposit->label(),
                ];
            }

            $options[] = [
                'value' => CheckoutPaymentChoice::OnlineFull->value,
                'label' => CheckoutPaymentChoice::OnlineFull->label(),
            ];
        }

        $options[] = [
            'value' => CheckoutPaymentChoice::PayLater->value,
            'label' => CheckoutPaymentChoice::PayLater->label(),
        ];

        return $options;
    }
}
