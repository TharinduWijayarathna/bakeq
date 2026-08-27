<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReportType;
use App\Livewire\Admin\ReportShow;
use App\Livewire\Admin\ReportsIndex;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\ShopSetting;
use App\Models\User;
use App\Models\WasteEntry;
use App\Support\BakeryReports;
use App\Support\StaffPermissions;
use Livewire\Livewire;

test('admin can open reports hub with month overview metrics', function () {
    ShopSetting::factory()->create(['labor_overhead_percent' => 10]);

    $cake = cake(['price' => 500000, 'name' => 'Report Cake']);
    $flour = Ingredient::factory()->create(['name' => 'Report Flour', 'unit_cost' => 100, 'current_stock' => 5000]);
    $recipe = Recipe::factory()->create(['cake_id' => $cake->id]);
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $flour->id,
        'quantity' => 100,
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'subtotal' => 500000,
        'payment_status' => PaymentStatus::Paid,
        'payment_amount' => 500000,
        'created_at' => now(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'cake_id' => $cake->id,
        'name' => $cake->name,
        'quantity' => 2,
        'unit_price' => 250000,
    ]);
    WasteEntry::factory()->create([
        'wasted_on' => now()->toDateString(),
        'ingredient_id' => $flour->id,
        'cost_impact' => 3000,
    ]);

    Livewire::actingAs(adminUser())
        ->test(ReportsIndex::class)
        ->assertOk()
        ->assertSee('Reports')
        ->assertSee('Real earnings')
        ->assertSee('Losses (waste)')
        ->assertSee('Profit & loss')
        ->assertSee('Ingredient usage')
        ->assertSee('Cake sales');

    $overview = BakeryReports::monthOverview();

    expect($overview['cakes_sold'])->toBe(2)
        ->and($overview['ingredient_kinds'])->toBe(1)
        ->and($overview['ingredient_cost'])->toBe(20000)
        ->and($overview['paid_earnings'])->toBe(500000)
        ->and($overview['waste_cost'])->toBe(3000);
});

test('report pages preview data and download pdfs', function () {
    $admin = adminUser();
    Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'subtotal' => 100000,
        'created_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(ReportShow::class, ['report' => ReportType::Sales->value])
        ->assertOk()
        ->assertSee('Cake sales')
        ->assertSee('Download PDF');

    $this->actingAs($admin)
        ->get(route('admin.reports.download', [
            'report' => ReportType::ProfitLoss->value,
            'month' => now()->format('Y-m'),
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    foreach (ReportType::cases() as $type) {
        $this->actingAs($admin)
            ->get(route('admin.reports.download', [
                'report' => $type->value,
                'month' => now()->format('Y-m'),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
});

test('unknown report type returns not found', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.reports.show', ['report' => 'not-a-report']))
        ->assertNotFound();
});

test('cashiers cannot access reports but managers can', function () {
    $cashier = User::factory()->cashier()->create();
    $manager = User::factory()->manager()->create();

    expect(StaffPermissions::allows($cashier, 'reports'))->toBeFalse()
        ->and(StaffPermissions::allows($manager, 'reports'))->toBeTrue();

    $this->actingAs($cashier)
        ->get(route('admin.reports.index'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('admin.reports.index'))
        ->assertOk();
});
