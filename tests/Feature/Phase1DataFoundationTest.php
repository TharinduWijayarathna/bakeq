<?php

use App\Actions\AddToCart;
use App\Enums\CustomerSource;
use App\Enums\OrderOrigin;
use App\Enums\OrderSource;
use App\Livewire\Admin\CakeForm;
use App\Livewire\Admin\CustomerIndex;
use App\Livewire\Admin\OrderIndex;
use App\Livewire\CheckoutPage;
use App\Models\Cake;
use App\Models\Category;
use App\Models\DesignerSetting;
use App\Models\Order;
use App\Models\ShopSetting;
use App\Models\User;
use App\Support\OrderTotals;
use Livewire\Livewire;

test('cake detail shows rich catalog fields', function () {
    $cake = cake([
        'description' => 'Rich cocoa sponge for celebrations.',
        'care_instructions' => 'Keep cool and refrigerate overnight.',
        'ingredients' => ['Cocoa', 'Flour'],
        'allergens' => ['Gluten'],
        'lead_days' => 4,
        'size_options' => [
            ['label' => '1 kg', 'servings' => '8-10', 'price' => 450000],
        ],
        'optional_addons' => [
            ['name' => 'Gold leaf', 'price' => 80000],
        ],
    ]);

    $this->get(route('cakes.show', $cake))
        ->assertOk()
        ->assertSee('Rich cocoa sponge for celebrations.')
        ->assertSee('Keep cool and refrigerate overnight.')
        ->assertSee('Cocoa')
        ->assertSee('Gluten')
        ->assertSee('Lead time: 4 days')
        ->assertSee('Gold leaf')
        ->assertSee('1 kg');
});

test('checkout stores itemized totals and online catalog origin', function () {
    DesignerSetting::factory()->create(['lead_days' => 3]);
    ShopSetting::factory()->create([
        'delivery_fee' => 50000,
        'tax_percent' => 10,
        'deposit_percent' => 20,
    ]);

    $user = customer([
        'address_line' => '88 Galle Road',
        'city' => 'Colombo',
    ]);
    $cake = cake(['price' => 450000]);

    app(AddToCart::class)->handle($user, cake: $cake);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('fulfillment_method', 'delivery')
        ->set('delivery_date', now()->addDays(4)->toDateString())
        ->set('delivery_address', '88 Galle Road, Colombo')
        ->set('notes', 'No nuts please')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect(route('orders.index'));

    $order = Order::query()->whereBelongsTo($user)->first();
    $expected = OrderTotals::calculate(450000);

    expect($order)->not->toBeNull()
        ->and($order->order_source)->toBe(OrderSource::Online)
        ->and($order->origin)->toBe(OrderOrigin::Catalog)
        ->and($order->notes)->toBe('No nuts please')
        ->and($order->subtotal)->toBe($expected['subtotal'])
        ->and($order->delivery_fee)->toBe($expected['delivery_fee'])
        ->and($order->tax_amount)->toBe($expected['tax_amount'])
        ->and($order->deposit_paid)->toBe($expected['deposit_paid'])
        ->and($order->total_due)->toBe($expected['total_due']);
});

test('admins can save the new cake catalog fields', function () {
    $admin = adminUser();
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(CakeForm::class)
        ->set('name', 'Allergen Aware Cake')
        ->set('category_id', $category->id)
        ->set('price_rupees', '5000')
        ->set('base_price_rupees', '4500')
        ->set('per_tier_addon_rupees', '1500')
        ->set('per_flavor_addon_rupees', '400')
        ->set('lead_days', '5')
        ->set('ingredients_text', "Flour\nEggs")
        ->set('allergens_text', 'Gluten, Eggs')
        ->set('size_options_text', "1 kg|8-10|4500\n2 kg|18-22|7500")
        ->set('optional_addons_text', 'Fresh florals|1200')
        ->set('care_instructions', 'Refrigerate after cutting.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.cakes.index'));

    $cake = Cake::query()->where('name', 'Allergen Aware Cake')->first();

    expect($cake)->not->toBeNull()
        ->and($cake->base_price)->toBe(450000)
        ->and($cake->lead_days)->toBe(5)
        ->and($cake->ingredients)->toBe(['Flour', 'Eggs'])
        ->and($cake->allergens)->toBe(['Gluten', 'Eggs'])
        ->and($cake->size_options[0]['label'])->toBe('1 kg')
        ->and($cake->optional_addons[0]['name'])->toBe('Fresh florals')
        ->and($cake->care_instructions)->toBe('Refrigerate after cutting.');
});

test('admin orders page has online and manual tabs with walk-in create', function () {
    $admin = adminUser();
    $customer = customer();
    $cake = cake(['price' => 450000]);
    Order::factory()->create(['user_id' => $customer->id, 'order_source' => OrderSource::Online]);
    Order::factory()->manual()->aiDesigned()->create(['user_id' => $customer->id]);

    Livewire::actingAs($admin)
        ->test(OrderIndex::class)
        ->assertSee('Online')
        ->assertSee('Manual')
        ->call('setTab', 'manual')
        ->assertSee('Create walk-in order')
        ->assertSee('AI Designed')
        ->set('user_id', $customer->id)
        ->set('cake_id', $cake->id)
        ->set('quantity', 1)
        ->set('delivery_date', now()->addDays(2)->toDateString())
        ->set('delivery_address', 'Counter pickup')
        ->call('createWalkIn')
        ->assertHasNoErrors();

    expect(Order::query()->where('order_source', OrderSource::Manual)->count())->toBe(2);
});

test('admin customers page supports manual customer creation', function () {
    $admin = adminUser();

    Livewire::actingAs($admin)
        ->test(CustomerIndex::class)
        ->call('setTab', 'manual')
        ->set('name', 'Walk-in Amara')
        ->set('email', 'amara.walkin@example.com')
        ->set('city', 'Kandy')
        ->call('createManual')
        ->assertHasNoErrors()
        ->assertSee('Walk-in Amara');

    $customer = User::query()->where('email', 'amara.walkin@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->customer_source)->toBe(CustomerSource::Manual)
        ->and($customer->isCustomer())->toBeTrue();
});
