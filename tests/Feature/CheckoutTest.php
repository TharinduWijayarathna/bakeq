<?php

use App\Actions\AddToCart;
use App\Enums\OrderStatus;
use App\Livewire\CheckoutPage;
use App\Models\DesignerSetting;
use App\Models\Order;
use Livewire\Livewire;

test('customers can place an order from the cart', function () {
    DesignerSetting::factory()->create(['lead_days' => 3]);
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
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect(route('orders.index'));

    $order = Order::query()->whereBelongsTo($user)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->items()->count())->toBe(1);
});
