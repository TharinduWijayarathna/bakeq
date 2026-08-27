<?php

use App\Ai\AdminAgent;
use App\Enums\OrderStatus;
use App\Livewire\Admin\AdminAgentChat;
use App\Models\AssistantMessage;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminAgentTools;
use App\Support\StaffPermissions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('admin agent tool declarations match registered handlers', function () {
    $declared = collect(AdminAgentTools::declarations())->pluck('name')->all();
    $registered = AdminAgentTools::names();

    expect($declared)->toEqualCanonicalizing($registered)
        ->and($registered)->toHaveCount(22);
});

test('admins can open the admin agent page', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.admin-agent'))
        ->assertOk()
        ->assertSee('Admin Agent');
});

test('cashiers cannot open the admin agent', function () {
    $this->actingAs(User::factory()->cashier()->create())
        ->get(route('admin.admin-agent'))
        ->assertForbidden();
});

test('admin agent offline mode returns dashboard summary via tools', function () {
    config(['services.gemini.key' => null]);

    Livewire::actingAs(adminUser())
        ->test(AdminAgentChat::class)
        ->set('message', 'Dashboard summary')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('Tools used:', false);

    expect(AssistantMessage::query()->where('session_id', 'like', 'admin-agent-%')->count())->toBe(2);
});

test('admin agent can look up and update an order without gemini', function () {
    config(['services.gemini.key' => null]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
    ]);

    Livewire::actingAs(adminUser())
        ->test(AdminAgentChat::class)
        ->set('message', 'Mark order #'.$order->id.' as confirmed')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('Order #'.$order->id);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});

test('admin agent tools respect permissions', function () {
    $cashier = User::factory()->cashier()->create();

    $result = AdminAgentTools::call('list_employees', [], $cashier);

    expect($result['ok'])->toBeFalse()
        ->and($result['summary'])->toContain('permission');
});

test('admin agent tools can create a category', function () {
    $admin = adminUser();

    $result = AdminAgentTools::call('create_category', ['name' => 'Agent Cupcakes'], $admin);

    expect($result['ok'])->toBeTrue()
        ->and(Category::query()->where('name', 'Agent Cupcakes')->exists())->toBeTrue();
});

test('admin agent tools list low stock ingredients', function () {
    Ingredient::factory()->create([
        'name' => 'Flour Low',
        'current_stock' => 1,
        'reorder_threshold' => 10,
    ]);

    $result = AdminAgentTools::call('list_low_stock', [], adminUser());

    expect($result['ok'])->toBeTrue()
        ->and($result['summary'])->toContain('need reordering')
        ->and(collect($result['data'])->pluck('name'))->toContain('Flour Low');
});

test('admin agent uses gemini function calling when configured', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Http::preventStrayRequests();

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'functionCall' => [
                                        'name' => 'get_order',
                                        'args' => ['order_id' => $order->id],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '**Order #'.$order->id.'** is pending for '.$order->user->name.'.',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
    ]);

    $reply = app(AdminAgent::class)->reply(
        'Show order #'.$order->id,
        [],
        adminUser(),
    );

    expect($reply['answer'])->toContain('Order #'.$order->id)
        ->and($reply['tools_used'])->toContain('get_order');

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return str_contains($request->url(), 'generateContent')
            && data_get($data, 'tools.0.functionDeclarations.0.name') === 'get_dashboard_summary';
    });
});

test('staff permissions include admin-agent for managers', function () {
    expect(StaffPermissions::allows(User::factory()->manager()->create(), 'admin-agent'))->toBeTrue()
        ->and(StaffPermissions::allows(User::factory()->baker()->create(), 'admin-agent'))->toBeFalse()
        ->and(StaffPermissions::routeAbility('admin.admin-agent'))->toBe('admin-agent');
});
