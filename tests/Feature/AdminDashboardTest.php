<?php

use App\Enums\OrderStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;
use Livewire\Livewire;

test('admins can view dashboard analytics', function () {
    $customer = customer();

    $delivered = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Delivered,
        'subtotal' => 500000,
    ]);

    OrderItem::factory()->create([
        'order_id' => $delivered->id,
        'name' => 'Ribbon Birthday Cake',
        'quantity' => 2,
        'unit_price' => 250000,
    ]);

    Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Pending,
        'subtotal' => 300000,
    ]);

    Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Cancelled,
        'subtotal' => 900000,
    ]);

    Livewire::actingAs(adminUser())
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Bakery dashboard')
        ->assertSee('Revenue · last 14 days')
        ->assertSee('Order status')
        ->assertSee(Money::format(800000))
        ->assertDontSee(Money::format(1700000), false)
        ->assertSee('Ribbon Birthday Cake')
        ->assertSee('2 sold')
        ->assertSee($customer->name)
        ->assertSee('Pending')
        ->assertSee('Delivered')
        ->assertSee('Cancelled');
});

test('dashboard shows an empty analytics state without orders', function () {
    Livewire::actingAs(adminUser())
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('No order activity in the last 14 days yet.')
        ->assertSee('No cake sales yet.');
});
