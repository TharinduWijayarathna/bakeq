<?php

use App\Actions\AddToCart;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductionStatus;
use App\Enums\WasteReason;
use App\Livewire\Admin\InvoiceIndex;
use App\Livewire\Admin\PosTerminal;
use App\Livewire\Admin\ProductionBoard;
use App\Livewire\Admin\WasteIndex;
use App\Livewire\CheckoutPage;
use App\Models\DesignerSetting;
use App\Models\Ingredient;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ShopSetting;
use App\Models\WasteEntry;
use Livewire\Livewire;

test('pos sale creates a manual confirmed order with receipt and invoice', function () {
    ShopSetting::factory()->create(['pickup_fee' => 0, 'tax_percent' => 0, 'deposit_percent' => 0]);
    $admin = adminUser();
    $customer = customer();
    $cake = cake(['price' => 450000, 'name' => 'POS Birthday']);

    Livewire::actingAs($admin)
        ->test(PosTerminal::class)
        ->set('user_id', $customer->id)
        ->set('payment_method', 'cash')
        ->set('discount_type', 'percent')
        ->set('discount_value', '10')
        ->set('lines.0.cake_id', $cake->id)
        ->set('lines.0.name', $cake->name)
        ->set('lines.0.quantity', 1)
        ->set('lines.0.unit_price_rupees', '4500')
        ->call('checkout')
        ->assertHasNoErrors();

    $order = Order::query()->whereBelongsTo($customer)->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->order_source)->toBe(OrderSource::Manual)
        ->and($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_method)->toBe(PaymentMethod::Cash)
        ->and($order->discount_amount)->toBe(45000)
        ->and($order->receipt_number)->not->toBeNull()
        ->and($order->invoice)->not->toBeNull();
});

test('production board can move an order between columns', function () {
    $admin = adminUser();
    $order = Order::factory()->create([
        'status' => OrderStatus::Confirmed,
        'production_status' => ProductionStatus::Planning,
    ]);

    Livewire::actingAs($admin)
        ->test(ProductionBoard::class)
        ->assertSee('#'.$order->id)
        ->call('move', $order->id, 'decorating')
        ->assertHasNoErrors();

    expect($order->fresh()->production_status)->toBe(ProductionStatus::Decorating);
});

test('waste log computes cost impact and totals', function () {
    $admin = adminUser();
    $flour = Ingredient::factory()->create(['unit_cost' => 100, 'current_stock' => 2000]);

    Livewire::actingAs($admin)
        ->test(WasteIndex::class)
        ->set('item_type', 'ingredient')
        ->set('ingredient_id', $flour->id)
        ->set('quantity', '50')
        ->set('reason', 'spoilage')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee($flour->name);

    $entry = WasteEntry::query()->first();

    expect($entry)->not->toBeNull()
        ->and($entry->reason)->toBe(WasteReason::Spoilage)
        ->and($entry->cost_impact)->toBe(5000)
        ->and((float) $flour->fresh()->current_stock)->toBe(1950.0);
});

test('invoices can be browsed and downloaded as pdf', function () {
    $admin = adminUser();
    $invoice = Invoice::factory()->create();

    Livewire::actingAs($admin)
        ->test(InvoiceIndex::class)
        ->assertSee($invoice->number);

    $this->actingAs($admin)
        ->get(route('admin.invoices.download', $invoice))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename='.$invoice->number.'.pdf');
});

test('checkout generates an invoice for online orders', function () {
    ShopSetting::factory()->create();
    $user = customer(['address_line' => '1 Main', 'city' => 'Colombo']);
    $cake = cake(['price' => 450000]);
    app(AddToCart::class)->handle($user, cake: $cake);

    DesignerSetting::factory()->create(['lead_days' => 1]);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('delivery_date', now()->addDays(2)->toDateString())
        ->set('delivery_address', '1 Main, Colombo')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->whereBelongsTo($user)->first();

    expect($order?->invoice)->not->toBeNull()
        ->and($order->production_status)->toBe(ProductionStatus::Planning);
});
