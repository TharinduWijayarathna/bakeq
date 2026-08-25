<?php

use App\Actions\AdjustInventoryForOrder;
use App\Enums\OrderStatus;
use App\Livewire\Admin\InventoryIndex;
use App\Livewire\Admin\OrderShow;
use App\Livewire\Admin\RecipeForm;
use App\Livewire\Admin\RecipeIndex;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\ShopSetting;
use App\Support\CakeCosting;
use Livewire\Livewire;

test('admins can manage inventory ingredients and alerts', function () {
    $admin = adminUser();
    Ingredient::factory()->lowStock()->create([
        'name' => 'Cake flour',
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    Livewire::actingAs($admin)
        ->test(InventoryIndex::class)
        ->assertSee('Low stock alert')
        ->assertSee('Expiring soon')
        ->assertSee('Cake flour')
        ->set('name', 'Caster sugar')
        ->set('unit', 'g')
        ->set('current_stock', '8000')
        ->set('unit_cost_rupees', '0.25')
        ->set('reorder_threshold', '1000')
        ->set('supplier', 'Sugar Co')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Caster sugar');

    expect(Ingredient::query()->where('name', 'Caster sugar')->value('unit_cost'))->toBe(25);
});

test('recipe costing includes labor overhead percent', function () {
    ShopSetting::factory()->create(['labor_overhead_percent' => 10]);
    $cake = cake(['price' => 450000]);
    $flour = Ingredient::factory()->create(['unit_cost' => 100]); // Rs.1 per unit
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id, 'size_label' => '']);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'quantity' => 1000, // cost 1000 * 100 = 100000 cents = Rs.1000
    ]);

    $costing = CakeCosting::forRecipe($recipe->fresh(['items.ingredient', 'cake']));

    // ingredient 100000 + 10% labor 10000 = 110000
    expect($costing['ingredient_cost'])->toBe(100000)
        ->and($costing['labor_cost'])->toBe(10000)
        ->and($costing['total_cost'])->toBe(110000)
        ->and($costing['sale_price'])->toBe(450000)
        ->and($costing['profit'])->toBe(340000)
        ->and($costing['margin_percent'])->toBe(75.6);
});

test('confirming an order deducts recipe stock and cancel restores it', function () {
    $admin = adminUser();
    $customer = customer();
    $cake = cake();
    $flour = Ingredient::factory()->create(['current_stock' => 5000, 'name' => 'Flour']);
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id]);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'quantity' => 500,
    ]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Pending,
        'stock_deducted' => false,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'cake_id' => $cake->id,
        'quantity' => 2,
        'unit_price' => $cake->price,
        'name' => $cake->name,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order])
        ->call('updateStatus', 'confirmed')
        ->assertHasNoErrors();

    expect((float) $flour->fresh()->current_stock)->toBe(4000.0)
        ->and($order->fresh()->stock_deducted)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order->fresh()])
        ->call('updateStatus', 'cancelled')
        ->assertHasNoErrors();

    expect((float) $flour->fresh()->current_stock)->toBe(5000.0)
        ->and($order->fresh()->stock_deducted)->toBeFalse();
});

test('confirming an order is blocked when stock is short', function () {
    $admin = adminUser();
    $customer = customer();
    $cake = cake();
    $flour = Ingredient::factory()->create(['current_stock' => 100, 'name' => 'Butter']);
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id]);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'quantity' => 250,
    ]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Pending,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'cake_id' => $cake->id,
        'quantity' => 1,
        'unit_price' => $cake->price,
        'name' => $cake->name,
    ]);

    Livewire::actingAs($admin)
        ->test(OrderShow::class, ['order' => $order])
        ->call('updateStatus', 'confirmed')
        ->assertHasErrors(['status']);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and((float) $flour->fresh()->current_stock)->toBe(100.0);
});

test('admins can create a recipe with searchable ingredients', function () {
    $admin = adminUser();
    $cake = cake();
    $egg = Ingredient::factory()->create(['name' => 'Farm eggs']);

    Livewire::actingAs($admin)
        ->test(RecipeForm::class)
        ->set('cake_id', $cake->id)
        ->set('size_label', '1 kg')
        ->set('lines.0.ingredient_id', $egg->id)
        ->set('lines.0.quantity', '6')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.recipes.index'));

    $recipe = Recipe::query()->where('cake_id', $cake->id)->first();

    expect($recipe)->not->toBeNull()
        ->and($recipe->size_label)->toBe('1 kg')
        ->and($recipe->items()->count())->toBe(1);
});

test('recipe index shows cost and margin', function () {
    $admin = adminUser();
    ShopSetting::factory()->create(['labor_overhead_percent' => 0]);
    $cake = cake(['price' => 500000, 'name' => 'Margin Cake']);
    $sugar = Ingredient::factory()->create(['unit_cost' => 50]);
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id, 'name' => 'Margin recipe']);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $sugar->id,
        'quantity' => 100,
    ]);

    Livewire::actingAs($admin)
        ->test(RecipeIndex::class)
        ->assertSee('Margin recipe')
        ->assertSee('Margin Cake');
});

test('inventory action requirements multiply recipe qty by order qty', function () {
    $cake = cake();
    $milk = Ingredient::factory()->create();
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id]);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $milk->id,
        'quantity' => 200,
    ]);

    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'cake_id' => $cake->id,
        'quantity' => 3,
    ]);

    $requirements = app(AdjustInventoryForOrder::class)->requirements($order->fresh(['items.cake.recipes.items']));

    expect($requirements->get($milk->id))->toBe(600.0);
});
