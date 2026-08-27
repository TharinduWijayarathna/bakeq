<?php

use App\Enums\FulfillmentMethod;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\OrderIndex;
use App\Livewire\Admin\OrderShow;
use App\Models\Order;
use Livewire\Livewire;

test('admin can update order status from the orders list', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'order_source' => OrderSource::Online,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderIndex::class)
        ->call('updateStatus', $order->id, 'baking')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Baking);
});

test('admin can update payment status from the orders list', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'order_source' => OrderSource::Online,
        'payment_status' => PaymentStatus::Unpaid,
        'subtotal' => 400000,
        'addons_total' => 0,
        'delivery_fee' => 50000,
        'tax_amount' => 0,
        'deposit_paid' => 0,
        'total_due' => 450000,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderIndex::class)
        ->call('updatePaymentStatus', $order->id, 'paid')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->total_due)->toBe(0)
        ->and($order->deposit_paid)->toBe(450000)
        ->and($order->paid_at)->not->toBeNull();
});

test('admin can mark an order unpaid again from the order show page', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'subtotal' => 400000,
        'addons_total' => 0,
        'delivery_fee' => 50000,
        'tax_amount' => 0,
        'deposit_paid' => 450000,
        'total_due' => 0,
        'paid_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order])
        ->call('updatePaymentStatus', 'unpaid')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->total_due)->toBe(450000)
        ->and($order->deposit_paid)->toBe(0)
        ->and($order->paid_at)->toBeNull();
});

test('admin can edit order details on the order show page', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'fulfillment_method' => FulfillmentMethod::Delivery,
        'delivery_date' => now()->addDay()->toDateString(),
        'delivery_address' => 'Old street',
        'notes' => 'Old notes',
    ]);

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order])
        ->set('fulfillment_method', FulfillmentMethod::Pickup->value)
        ->set('delivery_date', now()->addDays(3)->toDateString())
        ->set('delivery_address', 'Counter pickup')
        ->set('notes', 'Ready after 4pm')
        ->call('saveDetails')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->fulfillment_method)->toBe(FulfillmentMethod::Pickup)
        ->and($order->delivery_address)->toBe('Counter pickup')
        ->and($order->notes)->toBe('Ready after 4pm')
        ->and($order->delivery_date->toDateString())->toBe(now()->addDays(3)->toDateString());
});

test('admin can update order status from the order show page', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order])
        ->call('updateStatus', 'confirmed')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});
