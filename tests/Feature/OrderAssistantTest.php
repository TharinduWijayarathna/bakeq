<?php

use App\Livewire\Admin\OrderIndex;
use App\Livewire\Admin\PosTerminal;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('order ai tab extracts structured fields and prefills pos', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'occasion' => 'Birthday',
                                    'flavor' => 'Chocolate',
                                    'servings' => '15',
                                    'date' => '2026-08-30',
                                    'time' => 'afternoon',
                                    'budget' => '8000',
                                    'style_notes' => 'Pink flowers',
                                    'customer_name' => 'Nimal',
                                    'phone' => null,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $admin = adminUser();

    Livewire::actingAs($admin)
        ->test(OrderIndex::class)
        ->call('setTab', 'ai')
        ->set('ai_message', 'Hi need a chocolate birthday cake for 15 people Saturday afternoon, budget 8000, pink flowers')
        ->call('extract')
        ->assertHasNoErrors()
        ->assertSet('ai_extracted', true)
        ->assertSet('occasion', 'Birthday')
        ->assertSet('flavor', 'Chocolate')
        ->assertSet('budget', '8000')
        ->call('sendToPos')
        ->assertRedirect(route('admin.pos'));

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'generateContent')
            && data_get($request->data(), 'generationConfig.responseMimeType') === 'application/json';
    });

    expect(session('pos_prefill.notes'))->toContain('Chocolate')
        ->and(session('pos_prefill.lines.0.name'))->toContain('Birthday')
        ->and(session('pos_prefill.lines.0.unit_price_rupees'))->toBe('8000');

    Livewire::actingAs($admin)
        ->test(PosTerminal::class)
        ->assertSet('notes', fn ($notes) => str_contains($notes, 'Pink flowers'))
        ->assertSet('lines.0.name', fn ($name) => str_contains($name, 'Birthday'));
});

test('order ai tab falls back to manual fields when ai fails', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
    ]);

    Livewire::actingAs(adminUser())
        ->test(OrderIndex::class)
        ->call('setTab', 'ai')
        ->set('ai_message', 'Need a vanilla wedding cake next Friday please')
        ->call('extract')
        ->assertSet('ai_extracted', true)
        ->assertSet('ai_failed', true)
        ->assertSet('style_notes', 'Need a vanilla wedding cake next Friday please')
        ->assertHasErrors('ai_message');
});

test('orders page includes the order ai tab for admins', function () {
    $this->actingAs(adminUser())
        ->get(route('admin.orders.index', ['tab' => 'ai']))
        ->assertOk()
        ->assertSee('Order AI')
        ->assertSee('Customer message');
});
