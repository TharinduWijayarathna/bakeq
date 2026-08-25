<?php

use App\Enums\SelectionType;
use App\Exceptions\GeminiRequestException;
use App\Jobs\GenerateCakePreview;
use App\Livewire\CakeAssistant;
use App\Livewire\CakeDesigner;
use App\Models\CakeDesign;
use App\Models\DesignerOption;
use App\Models\DesignerOptionGroup;
use App\Models\DesignerSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(customer());
});

test('the designer stores a nano banana preview from gemini', function () {
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

    [$group, $option] = designerSelection();

    Livewire::test(CakeDesigner::class)
        ->set('tiers', 1)
        ->call('selectOption', $group->id, $option->id)
        ->call('generate')
        ->assertHasNoErrors();

    $design = CakeDesign::query()->first();

    expect($design)->not->toBeNull()
        ->and($design->preview_path)->toStartWith('designs/')
        ->and($design->preview_path)->toEndWith('.png')
        ->and($design->previewUrl())->toStartWith('/storage/designs/');

    Storage::disk('public')->assertExists($design->preview_path);

    Http::assertSent(function (Request $request): bool {
        $prompt = (string) data_get($request->data(), 'contents.0.parts.0.text');

        return str_contains($request->url(), 'models/gemini-3.1-flash-lite-image:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && data_get($request->data(), 'generationConfig.responseModalities') === ['IMAGE']
            && data_get($request->data(), 'generationConfig.imageConfig.imageSize') === '1K'
            && data_get($request->data(), 'generationConfig.thinkingConfig.thinkingLevel') === 'minimal'
            && str_contains($prompt, 'Chocolate');
    });
});

test('the designer falls back to demo previews when gemini fails', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Exceptions::fake();
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'unavailable']], 503),
    ]);

    [$group, $option] = designerSelection();

    Livewire::test(CakeDesigner::class)
        ->set('tiers', 1)
        ->call('selectOption', $group->id, $option->id)
        ->call('generate')
        ->assertHasErrors(['generate']);

    expect(CakeDesign::query()->first()->preview_path)->toContain('images/previews/');

    Exceptions::assertReported(GeminiRequestException::class);
});

test('the designer falls back to demo previews when gemini times out', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Exceptions::fake();
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::failedConnection(),
    ]);

    [$group, $option] = designerSelection();

    Livewire::test(CakeDesigner::class)
        ->set('tiers', 1)
        ->call('selectOption', $group->id, $option->id)
        ->call('generate')
        ->assertHasErrors(['generate']);

    expect(CakeDesign::query()->first()->preview_path)->toContain('images/previews/');

    Exceptions::assertReported(ConnectionException::class);
});

test('the designer skips gemini when no api key is configured', function () {
    Http::fake();

    [$group, $option] = designerSelection();

    Livewire::test(CakeDesigner::class)
        ->set('tiers', 1)
        ->call('selectOption', $group->id, $option->id)
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSet('generating', false);

    Http::assertNothingSent();
    expect(CakeDesign::query()->first()->preview_path)->toContain('images/previews/');
});

test('the designer queues cake preview generation', function () {
    Queue::fake();

    [$group, $option] = designerSelection();

    Livewire::test(CakeDesigner::class)
        ->set('tiers', 1)
        ->call('selectOption', $group->id, $option->id)
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSet('generating', true);

    $design = CakeDesign::query()->first();

    expect($design)->not->toBeNull()
        ->and($design->preview_path)->toBeNull();

    Queue::assertPushed(GenerateCakePreview::class, fn (GenerateCakePreview $job): bool => $job->designId === $design->id);
});

test('the designer shows the preview once the queued job finishes', function () {
    Queue::fake();

    [$group, $option] = designerSelection();

    $component = Livewire::test(CakeDesigner::class)
        ->set('tiers', 1)
        ->call('selectOption', $group->id, $option->id)
        ->call('generate');

    $design = CakeDesign::query()->first();
    $design->update(['preview_path' => 'designs/done.png']);

    $component->call('refreshPreview')
        ->assertSet('generating', false)
        ->assertSee('/storage/designs/done.png', false);
});

test('a failed preview job stores a demo image', function () {
    $option = DesignerOption::factory()->create();

    $design = CakeDesign::factory()->create([
        'user_id' => auth()->id(),
        'preview_path' => null,
        'tiers' => 1,
        'selections' => [
            'tiers' => 1,
            'option_ids' => [$option->id],
        ],
    ]);

    (new GenerateCakePreview($design->id))->failed(new RuntimeException('queue worker crashed'));

    expect($design->fresh()->preview_path)->toContain('images/previews/');
});

test('the assistant answers with gemini flash', function () {
    config(['services.gemini.key' => 'test-gemini-key']);
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => "**Vanilla sponge** is the most loved Rushq cakes flavour.\n\n- Soft and simple\n- A favourite for birthdays"],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    Livewire::actingAs(customer())
        ->test(CakeAssistant::class)
        ->set('message', 'What flavour options do you have?')
        ->call('ask')
        ->assertHasNoErrors()
        ->assertSee('<strong>Vanilla sponge</strong>', false)
        ->assertSee('<li>Soft and simple</li>', false);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), 'models/gemini-3.5-flash:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key');
    });
});

/**
 * @return array{0: DesignerOptionGroup, 1: DesignerOption}
 */
function designerSelection(): array
{
    DesignerSetting::factory()->create(['min_tiers' => 1, 'max_tiers' => 3]);

    $group = DesignerOptionGroup::factory()->create([
        'name' => 'Flavour',
        'is_required' => true,
        'selection_type' => SelectionType::Single,
        'min_select' => 1,
        'max_select' => 1,
    ]);

    $option = DesignerOption::factory()->create([
        'designer_option_group_id' => $group->id,
        'name' => 'Chocolate',
    ]);

    return [$group, $option];
}
