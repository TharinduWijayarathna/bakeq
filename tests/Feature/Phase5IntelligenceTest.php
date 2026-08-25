<?php

use App\Enums\OrderStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\ShopSetting;
use App\Models\WasteEntry;
use App\Support\BakeryAnalytics;
use App\Support\DemandForecast;
use App\Support\Money;
use App\Support\ProcurementSuggestions;
use Livewire\Livewire;

test('month summary includes budget progress, waste, and gross profit from recipes', function () {
    ShopSetting::factory()->create([
        'monthly_revenue_budget' => 1000000,
        'labor_overhead_percent' => 10,
    ]);

    $cake = cake(['price' => 500000, 'name' => 'Budget Cake']);
    $flour = Ingredient::factory()->create(['unit_cost' => 100, 'current_stock' => 5000]);
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id]);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'quantity' => 100,
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'subtotal' => 500000,
        'created_at' => now(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'cake_id' => $cake->id,
        'name' => $cake->name,
        'quantity' => 1,
        'unit_price' => 500000,
    ]);

    WasteEntry::factory()->create([
        'wasted_on' => now()->toDateString(),
        'ingredient_id' => $flour->id,
        'cost_impact' => 2000,
    ]);

    $summary = BakeryAnalytics::monthSummary();

    // ingredient 100*100=10000 + 10% labor = 11000 cogs
    expect($summary['revenue'])->toBe(500000)
        ->and($summary['budget'])->toBe(1000000)
        ->and($summary['budget_progress_percent'])->toBe(50.0)
        ->and($summary['cogs'])->toBe(11000)
        ->and($summary['gross_profit'])->toBe(489000)
        ->and($summary['waste_cost'])->toBe(2000)
        ->and($summary['net_profit'])->toBe(487000);
});

test('demand forecast averages recent weeks and explains the method', function () {
    foreach ([3, 2, 1] as $weeksAgo) {
        Order::factory()->count(2)->create([
            'status' => OrderStatus::Delivered,
            'created_at' => now()->startOfWeek()->subWeeks($weeksAgo)->addDay(),
        ]);
    }

    // Current week: 4 orders
    Order::factory()->count(4)->create([
        'status' => OrderStatus::Delivered,
        'created_at' => now()->startOfWeek()->addDay(),
    ]);

    $forecast = DemandForecast::weekly(4, 4);

    expect($forecast['lookback_weeks'])->toBe(4)
        ->and($forecast['horizon_weeks'])->toBe(4)
        ->and($forecast['forecast'])->toHaveCount(4)
        ->and($forecast['summary'])->toContain('moving average')
        ->and($forecast['average_weekly_orders'])->toBeGreaterThan(0);
});

test('procurement suggests ingredients short for forecasted demand', function () {
    ShopSetting::factory()->create();

    $cake = cake(['name' => 'Proc Cake', 'price' => 400000]);
    $flour = Ingredient::factory()->create([
        'name' => 'Flour',
        'unit_cost' => 50,
        'current_stock' => 5,
        'reorder_threshold' => 20,
    ]);
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id]);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'quantity' => 100,
    ]);

    foreach ([3, 2, 1] as $weeksAgo) {
        $order = Order::factory()->create([
            'status' => OrderStatus::Delivered,
            'created_at' => now()->startOfWeek()->subWeeks($weeksAgo)->addDay(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'cake_id' => $cake->id,
            'name' => $cake->name,
            'quantity' => 2,
            'unit_price' => 400000,
        ]);
    }

    // Force a short horizon with meaningful expected units via forDays
    $result = ProcurementSuggestions::forDays(7, 4);

    expect($result['expected_orders'])->toBeGreaterThan(0)
        ->and($result['items'])->not->toBeEmpty()
        ->and($result['items'][0]['name'])->toBe('Flour')
        ->and($result['items'][0]['suggested_qty'])->toBeGreaterThan(0)
        ->and($result['summary'])->toContain('expected orders');
});

test('dashboard shows intelligence sections and can save the monthly budget', function () {
    ShopSetting::factory()->create(['monthly_revenue_budget' => 20000000]);

    $admin = adminUser();

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('This month vs budget')
        ->assertSee('Demand · next')
        ->assertSee('Suggested reorders')
        ->assertSee('moving average')
        ->set('monthly_budget_rupees', '750000')
        ->call('saveBudget')
        ->assertHasNoErrors();

    expect(ShopSetting::current()->monthly_revenue_budget)->toBe(Money::rupeesToCents(750000));
});

test('revenue by category groups catalog sales', function () {
    $cake = cake(['name' => 'Cat Cake']);
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'subtotal' => 300000,
        'created_at' => now(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'cake_id' => $cake->id,
        'name' => $cake->name,
        'quantity' => 1,
        'unit_price' => 300000,
    ]);

    $categories = BakeryAnalytics::revenueByCategory();

    expect($categories)->not->toBeEmpty()
        ->and($categories[0]['revenue'])->toBe(300000)
        ->and($categories[0]['name'])->toBe($cake->category->name);
});
