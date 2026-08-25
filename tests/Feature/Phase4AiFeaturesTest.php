<?php

use App\Actions\AddToCart;
use App\Contracts\PromptCakeImageGenerator;
use App\Enums\OrderOrigin;
use App\Jobs\GeneratePromptCakePreview;
use App\Livewire\CakeDesigner;
use App\Livewire\CakeShow;
use App\Livewire\CheckoutPage;
use App\Models\CakeDesign;
use App\Models\DesignerSetting;
use App\Models\Order;
use App\Models\ShopSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(customer());
});

test('describe tab queues a prompt preview and stores notes', function () {
    Queue::fake();
    DesignerSetting::factory()->create(['base_price' => 650000]);

    Livewire::test(CakeDesigner::class)
        ->call('setMode', 'describe')
        ->set('prompt', 'Pink two-tier birthday cake with fresh strawberries')
        ->set('cartNotes', 'No nuts')
        ->call('generateFromPrompt')
        ->assertHasNoErrors()
        ->assertSet('generating', true);

    $design = CakeDesign::query()->first();

    expect($design)->not->toBeNull()
        ->and($design->selections['mode'])->toBe('prompt')
        ->and($design->selections['origin'])->toBe(OrderOrigin::AiDesigner->value)
        ->and($design->selections['prompt'])->toContain('strawberries')
        ->and($design->selections['customer_notes'])->toBe('No nuts')
        ->and($design->estimated_price)->toBe(650000);

    Queue::assertPushed(GeneratePromptCakePreview::class, fn (GeneratePromptCakePreview $job): bool => $job->designId === $design->id);
});

test('prompt preview generator stores a gemini image', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Storage::fake('public');
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'inlineData' => [
                                    'mimeType' => 'image/png',
                                    'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $design = CakeDesign::factory()->create([
        'user_id' => auth()->id(),
        'preview_path' => null,
        'selections' => [
            'mode' => 'prompt',
            'prompt' => 'Chocolate drip cake with gold leaf',
        ],
    ]);

    (new GeneratePromptCakePreview($design->id))->handle(app(PromptCakeImageGenerator::class));

    $design->refresh();

    expect($design->preview_path)->toStartWith('designs/')
        ->and($design->preview_path)->toEndWith('.png');

    Storage::disk('public')->assertExists($design->preview_path);

    Http::assertSent(function (Request $request): bool {
        $prompt = (string) data_get($request->data(), 'contents.0.parts.0.text');

        return str_contains($request->url(), 'models/gemini-3.1-flash-lite-image:generateContent')
            && str_contains($prompt, 'Chocolate drip cake');
    });
});

test('cake redesign queues preview with ai_redesign origin and checkout keeps it', function () {
    Queue::fake();
    DesignerSetting::factory()->create(['lead_days' => 3]);
    ShopSetting::factory()->create();

    $user = auth()->user();
    $cake = cake(['name' => 'Berry Bloom', 'price' => 480000, 'description' => 'Berry sponge']);

    Livewire::test(CakeShow::class, ['cake' => $cake])
        ->call('openRedesign')
        ->set('redesignPrompt', 'Add gold leaf and swap berries for blueberries')
        ->set('redesignNotes', 'Pickup Saturday')
        ->call('generateRedesign')
        ->assertHasNoErrors()
        ->assertSet('redesignGenerating', true);

    $design = CakeDesign::query()->latest('id')->first();

    expect($design->selections['mode'])->toBe('redesign')
        ->and($design->selections['origin'])->toBe(OrderOrigin::AiRedesign->value)
        ->and($design->selections['cake_name'])->toBe('Berry Bloom')
        ->and($design->estimated_price)->toBe(480000);

    Queue::assertPushed(GeneratePromptCakePreview::class);

    $design->update(['preview_path' => 'designs/redesign.png']);

    app(AddToCart::class)->handle($user, design: $design);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('fulfillment_method', 'pickup')
        ->set('delivery_date', now()->addDays(4)->toDateString())
        ->set('delivery_address', 'Shop pickup')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->whereBelongsTo($user)->latest('id')->first();

    expect($order->origin)->toBe(OrderOrigin::AiRedesign)
        ->and($order->notes)->toContain('Pickup Saturday');
});

test('failed prompt preview job falls back to demo image', function () {
    $design = CakeDesign::factory()->create([
        'user_id' => auth()->id(),
        'preview_path' => null,
        'selections' => [
            'mode' => 'prompt',
            'prompt' => 'Any celebration cake',
        ],
    ]);

    (new GeneratePromptCakePreview($design->id))->failed(new RuntimeException('worker crashed'));

    expect($design->fresh()->preview_path)->toContain('images/previews/');
});
