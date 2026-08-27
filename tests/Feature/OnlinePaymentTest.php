<?php

use App\Actions\AddToCart;
use App\Actions\MarkOrderPaidFromStripe;
use App\Contracts\StripeGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\OrderShow;
use App\Livewire\CheckoutPage;
use App\Models\DesignerSetting;
use App\Models\Order;
use App\Models\ShopSetting;
use Livewire\Livewire;

beforeEach(function () {
    DesignerSetting::factory()->create(['lead_days' => 3]);
    ShopSetting::factory()->create([
        'online_payments_enabled' => true,
        'deposit_percent' => 50,
        'delivery_fee' => 50000,
        'tax_percent' => 0,
    ]);
});

test('customers can place a pay later order without online payment', function () {
    $user = customer([
        'address_line' => '88 Galle Road',
        'city' => 'Colombo',
    ]);
    $cake = cake(['price' => 450000]);

    app(AddToCart::class)->handle($user, cake: $cake);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('delivery_date', now()->addDays(4)->toDateString())
        ->set('delivery_address', '88 Galle Road, Colombo')
        ->set('payment_choice', 'pay_later')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect(route('orders.index'));

    $order = Order::query()->whereBelongsTo($user)->first();

    expect($order)->not->toBeNull()
        ->and($order->payment_method)->toBe(PaymentMethod::PayLater)
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->deposit_paid)->toBe(0)
        ->and($order->total_due)->toBe(500000)
        ->and($order->payment_amount)->toBe(0);
});

test('customers paying full online are redirected to the payment gateway', function () {
    config([
        'stripe.enabled' => true,
        'stripe.secret_key' => 'test_secret_key',
    ]);

    $this->mock(StripeGateway::class, function ($mock): void {
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn([
                'id' => 'cs_test_full',
                'url' => 'https://pay.example.test/cs_test_full',
            ]);
    });

    $user = customer([
        'address_line' => '88 Galle Road',
        'city' => 'Colombo',
    ]);
    $cake = cake(['price' => 450000]);

    app(AddToCart::class)->handle($user, cake: $cake);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('delivery_date', now()->addDays(4)->toDateString())
        ->set('delivery_address', '88 Galle Road, Colombo')
        ->set('payment_choice', 'online_full')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect('https://pay.example.test/cs_test_full');

    $order = Order::query()->whereBelongsTo($user)->first();

    expect($order)->not->toBeNull()
        ->and($order->payment_method)->toBe(PaymentMethod::Online)
        ->and($order->payment_status)->toBe(PaymentStatus::AwaitingPayment)
        ->and($order->payment_amount)->toBe(500000)
        ->and($order->deposit_paid)->toBe(0)
        ->and($order->stripe_checkout_id)->toBe('cs_test_full');
});

test('customers can start an online deposit payment when deposit percent is set', function () {
    config([
        'stripe.enabled' => true,
        'stripe.secret_key' => 'test_secret_key',
    ]);

    $this->mock(StripeGateway::class, function ($mock): void {
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn([
                'id' => 'cs_test_deposit',
                'url' => 'https://pay.example.test/cs_test_deposit',
            ]);
    });

    $user = customer();
    $cake = cake(['price' => 450000]);
    app(AddToCart::class)->handle($user, cake: $cake);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('delivery_date', now()->addDays(4)->toDateString())
        ->set('delivery_address', '88 Galle Road, Colombo')
        ->set('payment_choice', 'online_deposit')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect('https://pay.example.test/cs_test_deposit');

    $order = Order::query()->whereBelongsTo($user)->first();

    expect($order->payment_amount)->toBe(250000)
        ->and($order->payment_status)->toBe(PaymentStatus::AwaitingPayment);
});

test('stripe webhook marks a full payment as paid when webhooks are enabled', function () {
    config(['stripe.webhooks_enabled' => true]);

    $order = Order::factory()->create([
        'subtotal' => 450000,
        'delivery_fee' => 50000,
        'tax_amount' => 0,
        'addons_total' => 0,
        'deposit_paid' => 0,
        'total_due' => 500000,
        'payment_method' => PaymentMethod::Online,
        'payment_status' => PaymentStatus::AwaitingPayment,
        'payment_amount' => 500000,
        'stripe_checkout_id' => 'cs_test_webhook',
    ]);

    $this->mock(StripeGateway::class, function ($mock): void {
        $mock->shouldReceive('parseWebhook')
            ->once()
            ->andReturn([
                'type' => 'checkout.session.completed',
                'checkout_id' => 'cs_test_webhook',
                'payment_id' => 'pi_test_123',
                'order_id' => null,
                'amount_total' => 500000,
                'payment_status' => 'paid',
            ]);
    });

    $this->post('/webhooks/stripe', [], [
        'HTTP_Stripe-Signature' => 'sig_test',
    ])->assertOk();

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->deposit_paid)->toBe(500000)
        ->and($order->total_due)->toBe(0)
        ->and($order->payment_amount)->toBe(500000)
        ->and($order->stripe_payment_id)->toBe('pi_test_123')
        ->and($order->paid_at)->not->toBeNull();
});

test('stripe webhook endpoint is a no-op when webhooks are disabled', function () {
    config(['stripe.webhooks_enabled' => false]);

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::AwaitingPayment,
        'payment_amount' => 500000,
        'stripe_checkout_id' => 'cs_ignored',
    ]);

    $this->mock(StripeGateway::class, function ($mock): void {
        $mock->shouldReceive('parseWebhook')->never();
    });

    $this->post('/webhooks/stripe')->assertOk()->assertSee('Webhooks disabled');

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::AwaitingPayment);
});

test('success return url confirms payment by retrieving the checkout session', function () {
    config([
        'stripe.enabled' => true,
        'stripe.secret_key' => 'test_secret_key',
        'stripe.webhooks_enabled' => false,
    ]);

    $user = customer();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subtotal' => 450000,
        'delivery_fee' => 50000,
        'tax_amount' => 0,
        'addons_total' => 0,
        'deposit_paid' => 0,
        'total_due' => 500000,
        'payment_method' => PaymentMethod::Online,
        'payment_status' => PaymentStatus::AwaitingPayment,
        'payment_amount' => 500000,
        'stripe_checkout_id' => 'cs_return',
    ]);

    $this->mock(StripeGateway::class, function ($mock): void {
        $mock->shouldReceive('retrieveCheckoutSession')
            ->once()
            ->with('cs_return')
            ->andReturn([
                'id' => 'cs_return',
                'payment_id' => 'pi_return',
                'order_id' => null,
                'amount_total' => 500000,
                'payment_status' => 'paid',
            ]);
    });

    $this->actingAs($user)
        ->get(route('checkout.payment.success', ['order' => $order, 'session_id' => 'cs_return']))
        ->assertRedirect(route('orders.index'));

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->stripe_payment_id)->toBe('pi_return')
        ->and($order->total_due)->toBe(0);
});

test('marking a deposit payment sets partially paid', function () {
    $order = Order::factory()->create([
        'subtotal' => 450000,
        'delivery_fee' => 50000,
        'tax_amount' => 0,
        'addons_total' => 0,
        'deposit_paid' => 0,
        'total_due' => 500000,
        'payment_method' => PaymentMethod::Online,
        'payment_status' => PaymentStatus::AwaitingPayment,
        'payment_amount' => 250000,
        'stripe_checkout_id' => 'cs_test_deposit_wh',
    ]);

    app(MarkOrderPaidFromStripe::class)->handle($order, [
        'checkout_id' => 'cs_test_deposit_wh',
        'payment_id' => 'pi_deposit',
        'amount_total' => 250000,
    ]);

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::PartiallyPaid)
        ->and($order->deposit_paid)->toBe(250000)
        ->and($order->total_due)->toBe(250000);
});

test('marking order paid from stripe is idempotent', function () {
    $order = Order::factory()->create([
        'subtotal' => 450000,
        'delivery_fee' => 50000,
        'tax_amount' => 0,
        'addons_total' => 0,
        'deposit_paid' => 0,
        'total_due' => 500000,
        'payment_status' => PaymentStatus::AwaitingPayment,
        'payment_amount' => 500000,
        'stripe_checkout_id' => 'cs_once',
    ]);

    $mark = app(MarkOrderPaidFromStripe::class);
    $mark->handle($order, [
        'checkout_id' => 'cs_once',
        'payment_id' => 'pi_once',
        'amount_total' => 500000,
    ]);
    $mark->handle($order->fresh(), [
        'checkout_id' => 'cs_once',
        'payment_id' => 'pi_other',
        'amount_total' => 500000,
    ]);

    $order->refresh();

    expect($order->stripe_payment_id)->toBe('pi_once')
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);
});

test('admin can mark outstanding balance as collected', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'subtotal' => 450000,
        'delivery_fee' => 50000,
        'tax_amount' => 0,
        'addons_total' => 0,
        'deposit_paid' => 250000,
        'total_due' => 250000,
        'payment_method' => PaymentMethod::Online,
        'payment_status' => PaymentStatus::PartiallyPaid,
        'payment_amount' => 250000,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order])
        ->call('markBalanceCollected')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->total_due)->toBe(0)
        ->and($order->deposit_paid)->toBe(500000);
});

test('admin order show displays payment details without branding the provider in labels', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'payment_method' => PaymentMethod::Online,
        'payment_status' => PaymentStatus::Paid,
        'payment_amount' => 500000,
        'stripe_payment_id' => 'pi_visible',
        'paid_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order])
        ->assertSee('Payment')
        ->assertSee('Online payment')
        ->assertSee('Paid')
        ->assertSee('pi_visible')
        ->assertSee('Payment reference')
        ->assertDontSee('Stripe');
});
